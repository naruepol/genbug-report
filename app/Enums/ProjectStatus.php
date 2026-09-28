<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum ProjectStatus: string
{
    use HasOptions;

    /** Hidden from the public; bug reports are not accepted. */
    case Draft = 'draft';

    /** Visible to the public; bug reports are accepted. */
    case Published = 'published';

    /** Still visible to the public, but no new bug reports are accepted. */
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Published => 'Published',
            self::Closed => 'Closed',
        };
    }

    public function isPubliclyVisible(): bool
    {
        return $this !== self::Draft;
    }

    public function acceptsBugReports(): bool
    {
        return $this === self::Published;
    }
}
