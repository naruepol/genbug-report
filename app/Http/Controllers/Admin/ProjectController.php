<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BugStatus;
use App\Enums\ProjectStatus;
use App\Enums\VerificationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Http\Resources\AdminBugResource;
use App\Http\Resources\ProjectResource;
use App\Models\AuditLog;
use App\Models\Project;
use App\Services\QrCodeService;
use App\Support\BugStats;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProjectController extends Controller
{
    public function __construct(private readonly QrCodeService $qrCodes) {}

    public function index(Request $request): Response
    {
        $search = mb_substr(trim((string) $request->query('search')), 0, 100);
        $status = ProjectStatus::tryFrom((string) $request->query('status'))?->value;

        $projects = Project::query()
            ->withCount([
                'bugs',
                'bugs as pending_bugs_count' => fn (Builder $query) => $query->where('verification_status', VerificationStatus::Pending),
                'bugs as fixed_bugs_count' => fn (Builder $query) => $query->whereIn('status', [BugStatus::Fixed, BugStatus::Closed]),
            ])
            ->when($search !== '', function (Builder $query) use ($search) {
                $pattern = '%'.addcslashes($search, '\\%_').'%';

                $query->where(fn (Builder $query) => $query
                    ->whereLike('name', $pattern)
                    ->orWhereLike('project_code', $pattern));
            })
            ->when($status, fn (Builder $query) => $query->where('status', $status))
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Admin/Projects/Index', [
            'projects' => ProjectResource::collection($projects),
            'filters' => ['search' => $search, 'status' => $status],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Projects/Create');
    }

    public function store(StoreProjectRequest $request): RedirectResponse
    {
        $data = $request->safe()->except('image');

        if ($request->hasFile('image')) {
            $data['image_url'] = $request->file('image')->store('projects', 'public');
        }

        $project = Project::create($data);

        if ($project->status === ProjectStatus::Published) {
            $this->qrCodes->generate($project);
        }

        AuditLog::record($request->user(), 'project.created', $project, $this->summary($project));

        Inertia::flash('success', "Project {$project->project_code} created.");

        return redirect()->route('admin.projects.show', $project);
    }

    public function show(Project $project): Response
    {
        $this->qrCodes->ensure($project);

        $recentBugs = $project->bugs()->latest('id')->take(10)->get();

        return Inertia::render('Admin/Projects/Show', [
            'project' => new ProjectResource($project),
            'stats' => BugStats::summary($project->bugs()),
            'charts' => BugStats::breakdowns($project->bugs()),
            'recentBugs' => AdminBugResource::collection($recentBugs),
            'qrCode' => [
                'svg' => $this->qrCodes->url($project, 'svg'),
                'png' => $this->qrCodes->url($project, 'png'),
                'target' => $project->publicUrl(),
            ],
        ]);
    }

    public function edit(Project $project): Response
    {
        return Inertia::render('Admin/Projects/Edit', [
            'project' => new ProjectResource($project),
        ]);
    }

    public function update(UpdateProjectRequest $request, Project $project): RedirectResponse
    {
        $data = $request->safe()->except(['image', 'remove_image']);
        $oldImage = $project->uploadedImagePath();

        if ($request->hasFile('image')) {
            $data['image_url'] = $request->file('image')->store('projects', 'public');
        } elseif ($request->boolean('remove_image')) {
            $data['image_url'] = null;
        }

        $project->fill($data);
        $changes = array_keys($project->getDirty());
        $project->save();

        if ($oldImage !== null && $project->image_url !== $oldImage) {
            Storage::disk('public')->delete($oldImage);
        }

        // The QR code encodes the project code, so refresh it whenever the project is public.
        if ($project->isPubliclyVisible()) {
            $this->qrCodes->generate($project);
        }

        AuditLog::record($request->user(), 'project.updated', $project, [
            ...$this->summary($project),
            'fields' => $changes,
        ]);

        Inertia::flash('success', 'Project updated.');

        return redirect()->route('admin.projects.show', $project);
    }

    public function destroy(Request $request, Project $project): RedirectResponse
    {
        $summary = $this->summary($project);

        $project->delete();

        AuditLog::record($request->user(), 'project.deleted', $project, $summary);

        Inertia::flash('success', "Project {$summary['project_code']} and its bugs were deleted.");

        return redirect()->route('admin.projects.index');
    }

    public function publish(Request $request, Project $project): RedirectResponse
    {
        $project->update(['status' => ProjectStatus::Published]);
        $this->qrCodes->generate($project);

        AuditLog::record($request->user(), 'project.published', $project, $this->summary($project));

        Inertia::flash('success', 'Project published. The QR code is ready to share.');

        return back();
    }

    public function close(Request $request, Project $project): RedirectResponse
    {
        $project->update(['status' => ProjectStatus::Closed]);

        AuditLog::record($request->user(), 'project.closed', $project, $this->summary($project));

        Inertia::flash('success', 'Project closed. It stays visible but no longer accepts bug reports.');

        return back();
    }

    /**
     * Turn public bug reporting on or off for one project. The project stays visible either way.
     */
    public function updateBugReporting(Request $request, Project $project): RedirectResponse
    {
        $request->validate(['enabled' => ['required', 'boolean']]);
        $enabled = $request->boolean('enabled');

        if ($project->bug_reporting_enabled !== $enabled) {
            $project->update(['bug_reporting_enabled' => $enabled]);

            AuditLog::record(
                $request->user(),
                $enabled ? 'project.bug_reporting_enabled' : 'project.bug_reporting_disabled',
                $project,
                $this->summary($project),
            );
        }

        Inertia::flash('success', match (true) {
            ! $enabled => 'Bug reporting is off. The project stays visible, but new reports are not accepted.',
            $project->acceptsBugReports() => 'Bug reporting is on. Visitors can report bugs.',
            default => 'Bug reporting is on. It takes effect while the project is published.',
        });

        return back();
    }

    public function generateQrCode(Request $request, Project $project): RedirectResponse
    {
        $this->qrCodes->generate($project);

        AuditLog::record($request->user(), 'project.qr_generated', $project, $this->summary($project));

        Inertia::flash('success', 'QR code generated.');

        return back();
    }

    public function downloadQrCode(Project $project, string $format): StreamedResponse
    {
        $this->qrCodes->ensure($project);

        return Storage::disk('public')->download(
            $this->qrCodes->path($project, $format),
            $this->qrCodes->downloadName($project, $format),
        );
    }

    /**
     * @return array{project_code: string, name: string}
     */
    private function summary(Project $project): array
    {
        return ['project_code' => $project->project_code, 'name' => $project->name];
    }
}
