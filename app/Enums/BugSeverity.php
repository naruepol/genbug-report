<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum BugSeverity: string
{
    use HasOptions;

    case Critical = 'critical';
    case High = 'high';
    case Medium = 'medium';
    case Low = 'low';

    public function label(): string
    {
        return match ($this) {
            self::Critical => 'Critical',
            self::High => 'High',
            self::Medium => 'Medium',
            self::Low => 'Low',
        };
    }
}
