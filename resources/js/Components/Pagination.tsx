import { formatNumber, classNames } from '@/lib/format';
import type { Paginated } from '@/types';
import { Link } from '@inertiajs/react';
import { ChevronLeft, ChevronRight } from 'lucide-react';

interface PaginationProps {
    meta: Paginated<unknown>['meta'];
    /** Props to reload; other props keep their current value. */
    only?: string[];
    /** Element id to scroll into view after changing page. */
    scrollTo?: string;
}

export default function Pagination({ meta, only, scrollTo }: PaginationProps) {
    if (meta.last_page <= 1) {
        return null;
    }

    const lastIndex = meta.links.length - 1;

    return (
        <nav aria-label="Pagination" className="flex flex-col items-center justify-between gap-3 sm:flex-row">
            <p className="text-sm text-ink-secondary">
                Showing <span className="font-medium text-ink">{meta.from}</span>–
                <span className="font-medium text-ink">{meta.to}</span> of{' '}
                <span className="font-medium text-ink">{formatNumber(meta.total)}</span>
            </p>

            <ul className="flex flex-wrap items-center gap-1">
                {meta.links.map((link, index) => {
                    const isPrevious = index === 0;
                    const isNext = index === lastIndex;
                    const content = isPrevious ? (
                        <>
                            <ChevronLeft className="size-4" aria-hidden="true" />
                            <span className="sr-only">Previous page</span>
                        </>
                    ) : isNext ? (
                        <>
                            <ChevronRight className="size-4" aria-hidden="true" />
                            <span className="sr-only">Next page</span>
                        </>
                    ) : (
                        link.label
                    );
                    const baseClass =
                        'inline-flex h-9 min-w-9 items-center justify-center rounded-lg px-2 text-sm font-medium tabular';

                    if (!link.url || link.label === '...') {
                        return (
                            <li key={index}>
                                <span aria-disabled="true" className={classNames(baseClass, 'text-ink-muted')}>
                                    {content}
                                </span>
                            </li>
                        );
                    }

                    return (
                        <li key={index}>
                            <Link
                                href={link.url}
                                only={only}
                                preserveState
                                preserveScroll
                                aria-current={link.active ? 'page' : undefined}
                                onSuccess={() => {
                                    if (scrollTo) {
                                        document.getElementById(scrollTo)?.scrollIntoView({ behavior: 'smooth', block: 'start' });
                                    }
                                }}
                                className={classNames(
                                    baseClass,
                                    link.active
                                        ? 'bg-brand text-white'
                                        : 'bg-surface text-ink ring-1 ring-hairline ring-inset hover:bg-chip',
                                )}
                            >
                                {content}
                            </Link>
                        </li>
                    );
                })}
            </ul>
        </nav>
    );
}
