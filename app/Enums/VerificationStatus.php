<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum VerificationStatus: string
{
    use HasOptions;

    case Pending = 'pending';
    case Verified = 'verified';
    case Rejected = 'rejected';
    case Duplicate = 'duplicate';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Verified => 'Verified',
            self::Rejected => 'Rejected',
            self::Duplicate => 'Duplicate',
        };
    }

    /**
     * The bug status that a verification outcome forces, if any.
     */
    public function impliedBugStatus(): ?BugStatus
    {
        return match ($this) {
            self::Rejected => BugStatus::Rejected,
            self::Duplicate => BugStatus::Duplicate,
            default => null,
        };
    }
}
