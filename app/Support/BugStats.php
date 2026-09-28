<?php

namespace App\Support;

use App\Enums\BugCategory;
use App\Enums\BugSeverity;
use App\Enums\BugStatus;
use App\Enums\VerificationStatus;
use BackedEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

/**
 * Aggregate bug counts for dashboards, computed in a single query where possible.
 */
final class BugStats
{
    /**
     * Totals by verification status and by bug status.
     *
     * @return array{total: int, verification: array<string, int>, status: array<string, int>}
     */
    public static function summary(Builder|Relation $query): array
    {
        $columns = ['count(*) as total'];
        $bindings = [];

        foreach (VerificationStatus::cases() as $case) {
            $columns[] = "sum(case when verification_status = ? then 1 else 0 end) as verification_{$case->value}";
            $bindings[] = $case->value;
        }

        foreach (BugStatus::cases() as $case) {
            $columns[] = "sum(case when status = ? then 1 else 0 end) as status_{$case->value}";
            $bindings[] = $case->value;
        }

        $row = (array) (clone $query)->toBase()->selectRaw(implode(', ', $columns), $bindings)->first();

        return [
            'total' => (int) ($row['total'] ?? 0),
            'verification' => self::pluck($row, 'verification_', VerificationStatus::cases()),
            'status' => self::pluck($row, 'status_', BugStatus::cases()),
        ];
    }

    /**
     * Chart data for "Bug by Status", "Bug by Severity" and "Bug by Category".
     *
     * @return array{status: list<array{value: ?string, label: string, count: int}>, severity: list<array{value: ?string, label: string, count: int}>, category: list<array{value: ?string, label: string, count: int}>}
     */
    public static function breakdowns(Builder|Relation $query): array
    {
        return [
            'status' => self::breakdown($query, 'status', BugStatus::cases()),
            'severity' => self::breakdown($query, 'severity', BugSeverity::cases(), 'Not assessed'),
            'category' => self::breakdown($query, 'category', BugCategory::cases(), 'Not specified'),
        ];
    }

    /**
     * @param  list<BackedEnum>  $cases
     * @return list<array{value: ?string, label: string, count: int}>
     */
    private static function breakdown(Builder|Relation $query, string $column, array $cases, ?string $nullLabel = null): array
    {
        $counts = (clone $query)->toBase()
            ->select($column)
            ->selectRaw('count(*) as aggregate')
            ->groupBy($column)
            ->pluck('aggregate', $column);

        $rows = array_map(fn (BackedEnum $case) => [
            'value' => $case->value,
            'label' => $case->label(),
            'count' => (int) ($counts[$case->value] ?? 0),
        ], $cases);

        if ($nullLabel !== null) {
            $rows[] = ['value' => null, 'label' => $nullLabel, 'count' => (int) ($counts[''] ?? 0)];
        }

        return $rows;
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  list<BackedEnum>  $cases
     * @return array<string, int>
     */
    private static function pluck(array $row, string $prefix, array $cases): array
    {
        $values = [];

        foreach ($cases as $case) {
            $values[$case->value] = (int) ($row[$prefix.$case->value] ?? 0);
        }

        return $values;
    }
}
