<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

/**
 * Workflow: Open → In Progress → Fixed → Closed, plus Rejected / Duplicate.
 */
enum BugStatus: string
{
    use HasOptions;

    case Open = 'open';
    case InProgress = 'in_progress';
    case Fixed = 'fixed';
    case Closed = 'closed';
    case Rejected = 'rejected';
    case Duplicate = 'duplicate';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Open',
            self::InProgress => 'In Progress',
            self::Fixed => 'Fixed',
            self::Closed => 'Closed',
            self::Rejected => 'Rejected',
            self::Duplicate => 'Duplicate',
        };
    }
}
