<?php

namespace App\Http\Resources;

use App\Models\Bug;
use App\Models\BugAttachment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The only shape in which bugs are sent to public pages.
 *
 * Fields are whitelisted explicitly. Never add reporter_name, reporter_email,
 * reporter_ip or admin_note here; the reporter is always shown as "Anonymous User".
 *
 * @mixin Bug
 */
class PublicBugResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'bug_code' => $this->bug_code,
            'title' => $this->title,
            'description' => $this->description,
            'category' => $this->category?->value,
            'severity' => $this->severity?->value,
            'priority' => $this->priority?->value,
            'score' => $this->score,
            'status' => $this->status->value,
            'verification_status' => $this->verification_status->value,
            'steps_to_reproduce' => $this->steps_to_reproduce,
            'expected_result' => $this->expected_result,
            'actual_result' => $this->actual_result,
            'screenshots' => $this->whenLoaded('attachments', fn () => $this->attachments->map(
                fn (BugAttachment $attachment) => [
                    'id' => $attachment->id,
                    'url' => $attachment->url(),
                    'mime_type' => $attachment->mime_type,
                ],
            )->values()),
            'project' => $this->whenLoaded('project', fn () => [
                'project_code' => $this->project->project_code,
                'name' => $this->project->name,
                'status' => $this->project->status->value,
                'accepts_bug_reports' => $this->project->acceptsBugReports(),
            ]),
            'created_at' => $this->created_at?->toJSON(),
            'updated_at' => $this->updated_at?->toJSON(),
        ];
    }
}
