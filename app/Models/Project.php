<?php

namespace App\Models;

use App\Enums\ProjectStatus;
use App\Services\QrCodeService;
use Database\Factories\ProjectFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

#[Fillable([
    'project_code',
    'name',
    'description',
    'image_url',
    'demo_url',
    'repository_url',
    'technology_stack',
    'presentation_date',
    'status',
    'bug_reporting_enabled',
])]
class Project extends Model
{
    /** @use HasFactory<ProjectFactory> */
    use HasFactory;

    protected $attributes = [
        'bug_reporting_enabled' => true,
    ];

    protected function casts(): array
    {
        return [
            'status' => ProjectStatus::class,
            'presentation_date' => 'date:Y-m-d',
            'bug_reporting_enabled' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::deleting(function (Project $project) {
            // Delete bugs one by one so their screenshots are removed from storage too.
            $project->bugs()->get()->each(fn (Bug $bug) => $bug->delete());

            $image = $project->uploadedImagePath();

            DB::afterCommit(function () use ($project, $image) {
                if ($image !== null) {
                    Storage::disk('public')->delete($image);
                }

                app(QrCodeService::class)->delete($project);
            });
        });
    }

    /**
     * @return HasMany<Bug, $this>
     */
    public function bugs(): HasMany
    {
        return $this->hasMany(Bug::class);
    }

    /**
     * Projects the public may see: Published and Closed (never Draft).
     */
    #[Scope]
    protected function publiclyVisible(Builder $query): void
    {
        $query->whereIn('status', [ProjectStatus::Published, ProjectStatus::Closed]);
    }

    /**
     * Project codes are stored upper-case; accept any case in URLs.
     */
    public function resolveRouteBinding($value, $field = null)
    {
        if ($field === 'project_code') {
            $value = Str::upper($value);
        }

        return parent::resolveRouteBinding($value, $field);
    }

    public function isPubliclyVisible(): bool
    {
        return $this->status->isPubliclyVisible();
    }

    /**
     * New reports need both a Published project and bug reporting switched on by the admin.
     */
    public function acceptsBugReports(): bool
    {
        return $this->status->acceptsBugReports() && $this->bug_reporting_enabled;
    }

    /**
     * Absolute public URL of the project page, based on APP_URL so QR codes are stable.
     */
    public function publicUrl(): string
    {
        return rtrim((string) config('app.url'), '/')
            .route('projects.show', ['project' => $this->project_code], absolute: false);
    }

    /**
     * Displayable image URL for either an uploaded image or an external URL.
     */
    protected function imageSrc(): Attribute
    {
        return Attribute::get(function (): ?string {
            if (blank($this->image_url)) {
                return null;
            }

            return $this->uploadedImagePath() === null
                ? $this->image_url
                : Storage::disk('public')->url($this->image_url);
        });
    }

    /**
     * Path on the public disk when the image was uploaded, or null for external URLs.
     */
    public function uploadedImagePath(): ?string
    {
        if (blank($this->image_url) || Str::startsWith($this->image_url, ['http://', 'https://'])) {
            return null;
        }

        return $this->image_url;
    }

    /**
     * The technology stack as a list, e.g. "Laravel, React" → ["Laravel", "React"].
     *
     * @return list<string>
     */
    public function technologies(): array
    {
        return array_values(array_filter(array_map(
            'trim',
            explode(',', (string) $this->technology_stack),
        )));
    }
}
