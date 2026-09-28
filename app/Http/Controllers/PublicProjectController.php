<?php

namespace App\Http\Controllers;

use App\Enums\BugStatus;
use App\Enums\ProjectStatus;
use App\Enums\VerificationStatus;
use App\Http\Resources\ProjectResource;
use App\Http\Resources\PublicBugResource;
use App\Models\Bug;
use App\Models\Project;
use App\Services\QrCodeService;
use App\Support\BugFilters;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PublicProjectController extends Controller
{
    public function home(): Response
    {
        $projects = $this->withBugCounts(Project::publiclyVisible())
            ->orderByRaw('status = ? desc', [ProjectStatus::Published->value])
            ->latest('id')
            ->take(6)
            ->get();

        return Inertia::render('Public/Home', [
            'projects' => ProjectResource::collection($projects),
        ]);
    }

    public function index(Request $request): Response
    {
        $search = mb_substr(trim((string) $request->query('search')), 0, 100);

        $projects = $this->withBugCounts(Project::publiclyVisible())
            ->when($search !== '', function (Builder $query) use ($search) {
                $pattern = '%'.addcslashes($search, '\\%_').'%';

                $query->where(fn (Builder $query) => $query
                    ->whereLike('name', $pattern)
                    ->orWhereLike('project_code', $pattern)
                    ->orWhereLike('technology_stack', $pattern));
            })
            ->orderByRaw('status = ? desc', [ProjectStatus::Published->value])
            ->latest('id')
            ->paginate(12)
            ->withQueryString();

        return Inertia::render('Public/Projects/Index', [
            'projects' => ProjectResource::collection($projects),
            'filters' => ['search' => $search],
        ]);
    }

    public function show(Request $request, Project $project, QrCodeService $qrCodes): Response
    {
        // Draft projects are hidden from the public; admins may preview them.
        abort_unless($project->isPubliclyVisible() || $request->user()?->isAdmin(), 404);

        if ($project->isPubliclyVisible()) {
            $qrCodes->ensure($project);
        }

        $filters = BugFilters::forPublic($request);

        $bugs = $project->bugs()
            ->search($filters['search'])
            ->filter($filters)
            ->latest('id')
            ->paginate(Bug::PER_PAGE)
            ->withQueryString();

        $bugTotals = $project->bugs()->toBase()
            ->selectRaw('count(*) as total')
            ->selectRaw('sum(case when verification_status = ? then 1 else 0 end) as verified', [VerificationStatus::Verified->value])
            ->selectRaw('sum(case when status in (?, ?) then 1 else 0 end) as fixed', [BugStatus::Fixed->value, BugStatus::Closed->value])
            ->first();

        return Inertia::render('Public/Projects/Show', [
            'project' => new ProjectResource($project),
            'stats' => [
                'total' => (int) $bugTotals->total,
                'verified' => (int) $bugTotals->verified,
                'fixed' => (int) $bugTotals->fixed,
            ],
            'bugs' => PublicBugResource::collection($bugs),
            'filters' => $filters,
            'qrCodeUrl' => $project->isPubliclyVisible() ? $qrCodes->url($project, 'svg') : null,
        ]);
    }

    /**
     * @param  Builder<Project>  $query
     * @return Builder<Project>
     */
    private function withBugCounts(Builder $query): Builder
    {
        return $query->withCount([
            'bugs',
            'bugs as verified_bugs_count' => fn (Builder $query) => $query->where('verification_status', VerificationStatus::Verified),
            'bugs as fixed_bugs_count' => fn (Builder $query) => $query->whereIn('status', [BugStatus::Fixed, BugStatus::Closed]),
        ]);
    }
}
