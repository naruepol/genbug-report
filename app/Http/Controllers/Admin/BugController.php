<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateBugRequest;
use App\Http\Resources\AdminBugResource;
use App\Models\AuditLog;
use App\Models\Bug;
use App\Models\Project;
use App\Support\BugFilters;
use BackedEnum;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BugController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = BugFilters::forAdmin($request);

        $bugs = Bug::query()
            ->with('project:id,name,project_code,status')
            ->search($filters['search'], true)
            ->filter($filters)
            ->latest('id')
            ->paginate(Bug::PER_PAGE)
            ->withQueryString();

        return Inertia::render('Admin/Bugs/Index', [
            'bugs' => AdminBugResource::collection($bugs),
            'filters' => $filters,
            'projects' => Project::query()->orderBy('name')->get(['id', 'name', 'project_code']),
        ]);
    }

    public function show(Bug $bug): Response
    {
        $bug->load(['project', 'attachments']);

        $activity = AuditLog::query()
            ->with('user:id,name')
            ->where('entity_type', class_basename($bug))
            ->where('entity_id', $bug->id)
            ->latest('id')
            ->take(20)
            ->get()
            ->map(fn (AuditLog $log) => [
                'id' => $log->id,
                'action' => $log->action,
                'metadata' => $log->metadata,
                'admin' => $log->user?->name,
                'created_at' => $log->created_at?->toJSON(),
            ]);

        return Inertia::render('Admin/Bugs/Show', [
            'bug' => new AdminBugResource($bug),
            'activity' => $activity,
        ]);
    }

    public function update(UpdateBugRequest $request, Bug $bug): RedirectResponse
    {
        $bug->applyReview($request->validated());

        $changes = $this->describeChanges($bug);

        if ($changes === []) {
            Inertia::flash('success', 'No changes to save.');

            return back();
        }

        $bug->save();

        AuditLog::record($request->user(), 'bug.updated', $bug, [
            'bug_code' => $bug->bug_code,
            'changes' => $changes,
        ]);

        Inertia::flash('success', "{$bug->bug_code} updated.");

        return back();
    }

    public function destroy(Request $request, Bug $bug): RedirectResponse
    {
        $summary = ['bug_code' => $bug->bug_code, 'title' => $bug->title];

        $bug->delete();

        AuditLog::record($request->user(), 'bug.deleted', $bug, $summary);

        Inertia::flash('success', "{$summary['bug_code']} was deleted.");

        return redirect()->route('admin.bugs.index');
    }

    /**
     * Old and new values of the reviewed fields, for the audit log. The note text is not copied.
     *
     * @return array<string, array{0: mixed, 1: mixed}|string>
     */
    private function describeChanges(Bug $bug): array
    {
        $changes = [];

        foreach (['verification_status', 'status', 'severity', 'priority', 'score'] as $field) {
            if ($bug->isDirty($field)) {
                $changes[$field] = [
                    $this->plain($bug->getOriginal($field)),
                    $this->plain($bug->getAttribute($field)),
                ];
            }
        }

        if ($bug->isDirty('admin_note')) {
            $changes['admin_note'] = 'updated';
        }

        return $changes;
    }

    private function plain(mixed $value): mixed
    {
        return $value instanceof BackedEnum ? $value->value : $value;
    }
}
