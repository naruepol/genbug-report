<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BugStatus;
use App\Enums\ProjectStatus;
use App\Enums\VerificationStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\AdminBugResource;
use App\Http\Resources\ProjectResource;
use App\Models\AuditLog;
use App\Models\Bug;
use App\Models\Project;
use App\Support\BugStats;
use Illuminate\Database\Eloquent\Builder;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        $projects = Project::query()
            ->withCount([
                'bugs',
                'bugs as pending_bugs_count' => fn (Builder $query) => $query->where('verification_status', VerificationStatus::Pending),
                'bugs as fixed_bugs_count' => fn (Builder $query) => $query->whereIn('status', [BugStatus::Fixed, BugStatus::Closed]),
            ])
            ->orderByDesc('pending_bugs_count')
            ->orderByDesc('bugs_count')
            ->take(6)
            ->get();

        $recentBugs = Bug::query()
            ->with('project:id,name,project_code,status')
            ->latest('id')
            ->take(8)
            ->get();

        $activity = AuditLog::query()
            ->with('user:id,name')
            ->latest('id')
            ->take(10)
            ->get()
            ->map(fn (AuditLog $log) => [
                'id' => $log->id,
                'action' => $log->action,
                'entity_type' => $log->entity_type,
                'entity_id' => $log->entity_id,
                'metadata' => $log->metadata,
                'admin' => $log->user?->name,
                'created_at' => $log->created_at?->toJSON(),
            ]);

        return Inertia::render('Admin/Dashboard', [
            'stats' => [
                'projects' => [
                    'total' => Project::count(),
                    'published' => Project::where('status', ProjectStatus::Published)->count(),
                ],
                'bugs' => BugStats::summary(Bug::query()),
            ],
            'projects' => ProjectResource::collection($projects),
            'recentBugs' => AdminBugResource::collection($recentBugs),
            'activity' => $activity,
        ]);
    }
}
