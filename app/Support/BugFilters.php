<?php

namespace App\Support;

use App\Enums\BugPriority;
use App\Enums\BugSeverity;
use App\Enums\BugStatus;
use App\Enums\VerificationStatus;
use BackedEnum;
use DateTimeImmutable;
use Illuminate\Http\Request;

/**
 * Reads bug search/filter query parameters and drops anything invalid,
 * so a bad value in the URL is ignored instead of causing an error.
 *
 * Keys match the query string (and the frontend filter state):
 * search, status, severity, verification, and for admins also project, priority, from, to.
 */
final class BugFilters
{
    /**
     * Public bug board: search by Bug ID / title; filter by status, severity, verification.
     *
     * @return array{search: string, status: ?string, severity: ?string, verification: ?string}
     */
    public static function forPublic(Request $request): array
    {
        return [
            'search' => self::search($request),
            'status' => self::enum($request, 'status', BugStatus::class),
            'severity' => self::enum($request, 'severity', BugSeverity::class),
            'verification' => self::enum($request, 'verification', VerificationStatus::class),
        ];
    }

    /**
     * Admin bug list: public filters plus project, priority and a created date range.
     *
     * @return array{search: string, status: ?string, severity: ?string, verification: ?string, project: ?int, priority: ?string, from: ?string, to: ?string}
     */
    public static function forAdmin(Request $request): array
    {
        $project = $request->query('project');

        return [
            ...self::forPublic($request),
            'project' => is_string($project) && ctype_digit($project) ? (int) $project : null,
            'priority' => self::enum($request, 'priority', BugPriority::class),
            'from' => self::date($request, 'from'),
            'to' => self::date($request, 'to'),
        ];
    }

    private static function search(Request $request): string
    {
        $value = $request->query('search');

        return is_string($value) ? mb_substr(trim($value), 0, 100) : '';
    }

    /**
     * @param  class-string<BackedEnum>  $enum
     */
    private static function enum(Request $request, string $key, string $enum): ?string
    {
        $value = $request->query($key);

        return is_string($value) ? $enum::tryFrom($value)?->value : null;
    }

    private static function date(Request $request, string $key): ?string
    {
        $value = $request->query($key);

        if (! is_string($value)) {
            return null;
        }

        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);

        return $date && $date->format('Y-m-d') === $value ? $value : null;
    }
}
