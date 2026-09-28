<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum BugPriority: string
{
    use HasOptions;

    case High = 'high';
    case Medium = 'medium';
    case Low = 'low';

    public function label(): string
    {
        return match ($this) {
            self::High => 'High',
            self::Medium => 'Medium',
            self::Low => 'Low',
        };
    }
}
