<?php

namespace App\Http\Resources;

use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Project data safe for both public and admin pages (projects hold no private fields).
 *
 * @mixin Project
 */
class ProjectResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'project_code' => $this->project_code,
            'name' => $this->name,
            'description' => $this->description,
            'image_url' => $this->image_src,
            'demo_url' => $this->demo_url,
            'repository_url' => $this->repository_url,
            'technology_stack' => $this->technology_stack,
            'technologies' => $this->technologies(),
            'presentation_date' => $this->presentation_date?->format('Y-m-d'),
            'status' => $this->status->value,
            'bug_reporting_enabled' => $this->bug_reporting_enabled,
            'accepts_bug_reports' => $this->acceptsBugReports(),
            'public_url' => $this->publicUrl(),
            'bugs_count' => $this->whenCounted('bugs'),
            'pending_bugs_count' => $this->whenCounted('pending_bugs'),
            'verified_bugs_count' => $this->whenCounted('verified_bugs'),
            'fixed_bugs_count' => $this->whenCounted('fixed_bugs'),
            'created_at' => $this->created_at?->toJSON(),
            'updated_at' => $this->updated_at?->toJSON(),
        ];
    }
}
