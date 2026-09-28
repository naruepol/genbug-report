<?php

namespace App\Http\Controllers;

use App\Enums\BugStatus;
use App\Enums\ProjectStatus;
use App\Enums\VerificationStatus;
use App\Http\Requests\StoreBugRequest;
use App\Http\Resources\ProjectResource;
use App\Http\Resources\PublicBugResource;
use App\Models\Bug;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class PublicBugController extends Controller
{
    /**
     * Request attribute telling the "bug-reports" rate limiter that a report was accepted.
     */
    public const ACCEPTED_ATTRIBUTE = 'bug_report.accepted';

    public function create(Project $project): Response|RedirectResponse
    {
        abort_unless($project->isPubliclyVisible(), 404);

        if (! $project->acceptsBugReports()) {
            return $this->reportsNotAccepted($project);
        }

        return Inertia::render('Public/Bugs/Create', [
            'project' => new ProjectResource($project),
        ]);
    }

    public function store(StoreBugRequest $request, Project $project): RedirectResponse
    {
        abort_unless($project->isPubliclyVisible(), 404);

        if (! $project->acceptsBugReports()) {
            return $this->reportsNotAccepted($project);
        }

        // Count this report toward the per-IP limit. The FormRequest holds its own copy of
        // the request attributes, so flag the underlying request the rate limiter sees.
        request()->attributes->set(self::ACCEPTED_ATTRIBUTE, true);

        // Bots that fill the honeypot get the normal success message, but nothing is saved.
        if ($request->isSpam()) {
            Inertia::flash('success', 'Thank you! Your bug report has been submitted.');

            return redirect()->route('projects.show', $project->project_code);
        }

        $bug = DB::transaction(function () use ($request, $project) {
            $bug = $project->bugs()->create([
                ...$request->safe()->only([
                    'title',
                    'description',
                    'category',
                    'steps_to_reproduce',
                    'expected_result',
                    'actual_result',
                    'page_screen',
                    'browser',
                    'operating_system',
                    'device',
                ]),
                'reporter_name' => $request->validated('name'),
                'reporter_email' => $request->validated('email'),
                'reporter_ip' => $request->ip(),
                'verification_status' => VerificationStatus::Pending,
                'status' => BugStatus::Open,
            ]);

            if ($screenshot = $request->file('screenshot')) {
                $bug->attachments()->create([
                    'file_name' => Str::limit(basename($screenshot->getClientOriginalName()), 250, ''),
                    'file_path' => $screenshot->store("bugs/{$bug->id}", 'public'),
                    'mime_type' => $screenshot->getMimeType(),
                    'file_size' => $screenshot->getSize(),
                ]);
            }

            return $bug;
        });

        Inertia::flash('success', "Thank you! Your bug report {$bug->bug_code} has been submitted.");

        return redirect()->route('bugs.show', $bug->bug_code);
    }

    public function show(Request $request, Bug $bug): Response
    {
        $bug->load(['project', 'attachments']);

        abort_unless($bug->project->isPubliclyVisible() || $request->user()?->isAdmin(), 404);

        return Inertia::render('Public/Bugs/Show', [
            'bug' => new PublicBugResource($bug),
        ]);
    }

    /**
     * Back to the project page when it is Closed or the admin turned bug reporting off.
     */
    private function reportsNotAccepted(Project $project): RedirectResponse
    {
        Inertia::flash('error', $project->status === ProjectStatus::Closed
            ? 'This project is closed and no longer accepts new bug reports.'
            : 'Bug reporting is turned off for this project at the moment.');

        return redirect()->route('projects.show', $project->project_code);
    }
}
