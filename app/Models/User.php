<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * An admin who signs in with Google. Public users never have accounts.
 */
#[Fillable(['google_id', 'name', 'email', 'avatar_url'])]
#[Hidden(['google_id'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory;

    /**
     * Whether the email is listed in the ADMIN_EMAILS allowlist.
     */
    public static function isAllowlisted(?string $email): bool
    {
        return $email !== null
            && in_array(strtolower(trim($email)), config('admin.emails', []), true);
    }

    public function isAdmin(): bool
    {
        return static::isAllowlisted($this->email);
    }

    /**
     * @return HasMany<AuditLog, $this>
     */
    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class, 'admin_id');
    }
}
