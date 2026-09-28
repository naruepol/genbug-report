<?php

namespace App\Models;

use App\Enums\BugCategory;
use App\Enums\BugPriority;
use App\Enums\BugSeverity;
use App\Enums\BugStatus;
use App\Enums\VerificationStatus;
use Database\Factories\BugFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * Reporter identity and the admin note are hidden from serialization by default.
 * Public responses must go through PublicBugResource, which whitelists fields.
 */
#[Fillable([
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
    'reporter_name',
    'reporter_email',
    'reporter_ip',
    'severity',
    'priority',
    'score',
    'verification_status',
    'status',
    'admin_note',
])]
#[Hidden(['reporter_name', 'reporter_email', 'reporter_ip', 'admin_note'])]
class Bug extends Model
{
    /** @use HasFactory<BugFactory> */
    use HasFactory;

    public const PER_PAGE = 20;

    protected $attributes = [
        'verification_status' => 'pending',
        'status' => 'open',
    ];

    protected function casts(): array
    {
        return [
            'category' => BugCategory::class,
            'severity' => BugSeverity::class,
            'priority' => BugPriority::class,
            'score' => 'integer',
            'verification_status' => VerificationStatus::class,
            'status' => BugStatus::class,
        ];
    }

    protected static function booted(): void
    {
        static::created(function (Bug $bug) {
            $bug->bug_code = static::codeFor($bug->id);
            $bug->saveQuietly();
        });

        // Delete attachments through Eloquent so their files are removed from storage.
        static::deleting(function (Bug $bug) {
            $bug->attachments()->get()->each(fn (BugAttachment $attachment) => $attachment->delete());
        });
    }

    /**
     * Human-friendly identifier derived from the id: 1 → BUG-001, 1234 → BUG-1234.
     */
    public static function codeFor(int $id): string
    {
        return sprintf('BUG-%03d', $id);
    }

    /**
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * @return HasMany<BugAttachment, $this>
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(BugAttachment::class);
    }

    /**
     * Bug codes are stored upper-case; accept any case in URLs.
     */
    public function resolveRouteBinding($value, $field = null)
    {
        if ($field === 'bug_code') {
            $value = Str::upper($value);
        }

        return parent::resolveRouteBinding($value, $field);
    }

    /**
     * Public search covers Bug ID and title; admin search also covers description and reporter email.
     */
    #[Scope]
    protected function search(Builder $query, ?string $term, bool $includePrivateFields = false): void
    {
        $term = trim((string) $term);

        if ($term === '') {
            return;
        }

        $pattern = '%'.addcslashes($term, '\\%_').'%';

        $query->where(function (Builder $query) use ($pattern, $includePrivateFields) {
            $query->whereLike('bug_code', $pattern)->orWhereLike('title', $pattern);

            if ($includePrivateFields) {
                $query->orWhereLike('description', $pattern)->orWhereLike('reporter_email', $pattern);
            }
        });
    }

    /**
     * Apply already-sanitized filters keyed like the query string (see App\Support\BugFilters).
     *
     * @param  array<string, mixed>  $filters
     */
    #[Scope]
    protected function filter(Builder $query, array $filters): void
    {
        $columns = [
            'project' => 'project_id',
            'status' => 'status',
            'severity' => 'severity',
            'priority' => 'priority',
            'verification' => 'verification_status',
        ];

        foreach ($columns as $key => $column) {
            if (filled($filters[$key] ?? null)) {
                $query->where($column, $filters[$key]);
            }
        }

        if (filled($filters['from'] ?? null)) {
            $query->whereDate('created_at', '>=', $filters['from']);
        }

        if (filled($filters['to'] ?? null)) {
            $query->whereDate('created_at', '<=', $filters['to']);
        }
    }

    /**
     * Apply an admin review (verification, severity, priority, score, status, note) while
     * keeping verification and status consistent: a Rejected or Duplicate verification
     * always has the matching status, and leaving that state reopens the bug.
     *
     * @param  array<string, mixed>  $review
     */
    public function applyReview(array $review): void
    {
        $this->fill($review);

        $closedOut = [BugStatus::Rejected, BugStatus::Duplicate];

        if ($this->isDirty('verification_status')) {
            $implied = $this->verification_status->impliedBugStatus();

            if ($implied !== null) {
                $this->status = $implied;
            } elseif (in_array($this->status, $closedOut, true)) {
                $this->status = BugStatus::Open;
            }
        } elseif ($this->isDirty('status')) {
            if (in_array($this->status, $closedOut, true)) {
                $this->verification_status = VerificationStatus::from($this->status->value);
            } elseif ($this->verification_status->impliedBugStatus() !== null) {
                $this->verification_status = VerificationStatus::Pending;
            }
        }
    }
}
