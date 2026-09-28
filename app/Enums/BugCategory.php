<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum BugCategory: string
{
    use HasOptions;

    case Functional = 'functional';
    case UiUx = 'ui_ux';
    case Performance = 'performance';
    case Compatibility = 'compatibility';
    case Content = 'content';
    case Other = 'other';
    case NotSure = 'not_sure';

    public function label(): string
    {
        return match ($this) {
            self::Functional => 'Functional',
            self::UiUx => 'UI/UX',
            self::Performance => 'Performance',
            self::Compatibility => 'Compatibility',
            self::Content => 'Content',
            self::Other => 'Other',
            self::NotSure => 'Not Sure',
        };
    }
}
