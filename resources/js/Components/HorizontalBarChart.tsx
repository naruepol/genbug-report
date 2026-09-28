import { formatNumber } from '@/lib/format';
import type { BreakdownRow } from '@/types';
import { useId } from 'react';

interface HorizontalBarChartProps {
    title: string;
    rows: BreakdownRow[];
    unit?: [singular: string, plural: string];
}

/**
 * One-series horizontal bar chart for counts per category.
 *
 * It is built as a real table (label · bar · value), so every value is readable as
 * text and by screen readers; hovering or focusing a row adds its share of the total.
 * Marks follow the data-viz specs: one hue (series slot 1), bars ≤ 20px thick,
 * square at the baseline with a 4px rounded data end, value at the bar tip.
 */
export default function HorizontalBarChart({ title, rows, unit = ['bug', 'bugs'] }: HorizontalBarChartProps) {
    const id = useId();
    const total = rows.reduce((sum, row) => sum + row.count, 0);
    const max = Math.max(1, ...rows.map((row) => row.count));

    return (
        <figure className="rounded-xl bg-surface p-4 ring-1 ring-hairline">
            <figcaption id={`${id}-title`} className="text-sm font-semibold text-ink">
                {title}
            </figcaption>

            {total === 0 ? (
                <p className="mt-6 mb-4 text-center text-sm text-ink-muted">No bugs yet</p>
            ) : (
                <table className="mt-3 w-full border-separate border-spacing-y-1.5 text-sm" aria-labelledby={`${id}-title`}>
                    <thead className="sr-only">
                        <tr>
                            <th scope="col">Category</th>
                            <th scope="col">Count</th>
                        </tr>
                    </thead>
                    <tbody>
                        {rows.map((row) => {
                            const share = Math.round((row.count / total) * 100);
                            const width = row.count === 0 ? 0 : Math.max((row.count / max) * 100, 1.5);

                            return (
                                <tr
                                    key={row.value ?? 'none'}
                                    tabIndex={0}
                                    className="group outline-none"
                                    aria-label={`${row.label}: ${row.count} ${row.count === 1 ? unit[0] : unit[1]}, ${share}%`}
                                >
                                    <th
                                        scope="row"
                                        className="w-28 max-w-28 pr-3 text-left align-middle text-xs font-normal text-ink-secondary group-hover:text-ink group-focus-visible:text-ink sm:w-32"
                                    >
                                        <span className="line-clamp-1">{row.label}</span>
                                    </th>
                                    <td className="relative border-l border-baseline py-0.5 align-middle">
                                        <div className="flex items-center">
                                            <div
                                                className="h-4 rounded-r-[4px] bg-series-1 transition-[filter,box-shadow] group-hover:brightness-110 group-focus-visible:ring-2 group-focus-visible:ring-brand group-focus-visible:ring-offset-1"
                                                style={{ width: `${width}%` }}
                                                aria-hidden="true"
                                            />
                                            <span className="ml-2 text-xs font-medium text-ink tabular">{formatNumber(row.count)}</span>
                                        </div>
                                        <div
                                            role="presentation"
                                            className="pointer-events-none absolute bottom-full left-2 z-10 mb-1 hidden rounded-md bg-surface px-2.5 py-1.5 text-xs whitespace-nowrap shadow-lg ring-1 ring-hairline group-hover:block group-focus-visible:block"
                                        >
                                            <span className="font-semibold text-ink tabular">
                                                {formatNumber(row.count)} {row.count === 1 ? unit[0] : unit[1]}
                                            </span>
                                            <span className="text-ink-secondary">
                                                {' '}
                                                · {share}% · {row.label}
                                            </span>
                                        </div>
                                    </td>
                                </tr>
                            );
                        })}
                    </tbody>
                </table>
            )}
        </figure>
    );
}
