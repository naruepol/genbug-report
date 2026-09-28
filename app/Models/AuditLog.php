<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Records admin actions, e.g. "bug.verified" on Bug #12.
 */
#[Fillable(['admin_id', 'action', 'entity_type', 'entity_id', 'metadata'])]
class AuditLog extends Model
{
    public const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    public static function record(?User $admin, string $action, Model $entity, array $metadata = []): self
    {
        return static::create([
            'admin_id' => $admin?->id,
            'action' => $action,
            'entity_type' => class_basename($entity),
            'entity_id' => $entity->getKey(),
            'metadata' => $metadata ?: null,
        ]);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}
