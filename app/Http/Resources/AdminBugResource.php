<?php

namespace App\Http\Resources;

use App\Models\Bug;
use App\Models\BugAttachment;
use Illuminate\Http\Request;

/**
 * Admin view of a bug: the public fields plus reporter information, environment and admin note.
 *
 * @mixin Bug
 */
class AdminBugResource extends PublicBugResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            ...parent::toArray($request),
            'page_screen' => $this->page_screen,
            'browser' => $this->browser,
            'operating_system' => $this->operating_system,
            'device' => $this->device,
            'reporter_name' => $this->reporter_name,
            'reporter_email' => $this->reporter_email,
            'reporter_ip' => $this->reporter_ip,
            'admin_note' => $this->admin_note,
            'screenshots' => $this->whenLoaded('attachments', fn () => $this->attachments->map(
                fn (BugAttachment $attachment) => [
                    'id' => $attachment->id,
                    'url' => $attachment->url(),
                    'mime_type' => $attachment->mime_type,
                    'file_name' => $attachment->file_name,
                    'file_size' => $attachment->file_size,
                ],
            )->values()),
            'project' => $this->whenLoaded('project', fn () => [
                'id' => $this->project->id,
                'project_code' => $this->project->project_code,
                'name' => $this->project->name,
                'status' => $this->project->status->value,
            ]),
        ];
    }
}
