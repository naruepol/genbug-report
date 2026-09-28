import { CategoryLabel, PriorityBadge, ScoreBadge, SeverityBadge, StatusBadge, VerificationBadge } from '@/Components/BugBadge';
import { formatDate } from '@/lib/format';
import type { AdminBug, PublicBug } from '@/types';
import { Link } from '@inertiajs/react';
import type { ReactNode } from 'react';

interface BugTableProps<T extends PublicBug> {
    bugs: T[];
    href: (bug: T) => string;
    /** Admin-only columns. */
    showProject?: boolean;
    showPriority?: boolean;
    showReported?: boolean;
    empty?: ReactNode;
    caption?: string;
}

/**
 * Bug list: a table on wider screens and stacked cards on phones.
 * Public boards show Bug ID, Title, Severity, Score, Status and Verification.
 */
export default function BugTable<T extends PublicBug | AdminBug>({
    bugs,
    href,
    showProject = false,
    showPriority = false,
    showReported = false,
    empty,
    caption = 'Bugs',
}: BugTableProps<T>) {
    if (bugs.length === 0) {
        return <>{empty}</>;
    }

    return (
        <>
            {/* Phones: cards */}
            <ul className="divide-y divide-hairline md:hidden">
                {bugs.map((bug) => (
                    <li key={bug.bug_code} className="relative px-4 py-3.5 hover:bg-page">
                        <div className="flex items-center justify-between gap-3">
                            <span className="text-xs font-semibold tracking-wide text-ink-secondary tabular">{bug.bug_code}</span>
                            <VerificationBadge status={bug.verification_status} />
                        </div>
                        <Link
                            href={href(bug)}
                            className="mt-1 block font-medium text-ink after:absolute after:inset-0 focus:outline-none"
                        >
                            {bug.title}
                        </Link>
                        {showProject && bug.project && (
                            <p className="mt-0.5 text-xs text-ink-secondary">{bug.project.name}</p>
                        )}
                        <div className="mt-2 flex flex-wrap items-center gap-1.5">
                            <StatusBadge status={bug.status} />
                            {bug.severity && <SeverityBadge severity={bug.severity} />}
                            {bug.score !== null && <ScoreBadge score={bug.score} />}
                            {showPriority && bug.priority && <PriorityBadge priority={bug.priority} />}
                        </div>
                        {showReported && <p className="mt-1.5 text-xs text-ink-muted">Reported {formatDate(bug.created_at)}</p>}
                    </li>
                ))}
            </ul>

            {/* Tablets and desktops: table */}
            <div className="hidden overflow-x-auto md:block">
                <table className="min-w-full text-left text-sm">
                    <caption className="sr-only">{caption}</caption>
                    <thead className="border-b border-hairline bg-page text-xs font-semibold tracking-wide text-ink-secondary uppercase">
                        <tr>
                            <th scope="col" className="px-4 py-2.5 whitespace-nowrap">
                                Bug ID
                            </th>
                            <th scope="col" className="px-4 py-2.5">
                                Title
                            </th>
                            {showProject && (
                                <th scope="col" className="px-4 py-2.5">
                                    Project
                                </th>
                            )}
                            <th scope="col" className="px-4 py-2.5">
                                Severity
                            </th>
                            {showPriority && (
                                <th scope="col" className="px-4 py-2.5">
                                    Priority
                                </th>
                            )}
                            <th scope="col" className="px-4 py-2.5">
                                Score
                            </th>
                            <th scope="col" className="px-4 py-2.5">
                                Status
                            </th>
                            <th scope="col" className="px-4 py-2.5">
                                Verification
                            </th>
                            {showReported && (
                                <th scope="col" className="px-4 py-2.5 whitespace-nowrap">
                                    Reported
                                </th>
                            )}
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-hairline">
                        {bugs.map((bug) => (
                            <tr key={bug.bug_code} className="align-top hover:bg-page">
                                <td className="px-4 py-3 font-semibold whitespace-nowrap text-ink-secondary tabular">
                                    <Link href={href(bug)} className="hover:text-link">
                                        {bug.bug_code}
                                    </Link>
                                </td>
                                <td className="max-w-md px-4 py-3">
                                    <Link href={href(bug)} className="font-medium text-ink hover:text-link hover:underline">
                                        {bug.title}
                                    </Link>
                                    {bug.category && (
                                        <div className="mt-1">
                                            <CategoryLabel category={bug.category} />
                                        </div>
                                    )}
                                </td>
                                {showProject && (
                                    <td className="px-4 py-3 text-ink-secondary">
                                        {bug.project?.name}
                                        <div className="text-xs text-ink-muted">{bug.project?.project_code}</div>
                                    </td>
                                )}
                                <td className="px-4 py-3">
                                    <SeverityBadge severity={bug.severity} />
                                </td>
                                {showPriority && (
                                    <td className="px-4 py-3">
                                        <PriorityBadge priority={bug.priority} />
                                    </td>
                                )}
                                <td className="px-4 py-3">
                                    <ScoreBadge score={bug.score} />
                                </td>
                                <td className="px-4 py-3">
                                    <StatusBadge status={bug.status} />
                                </td>
                                <td className="px-4 py-3">
                                    <VerificationBadge status={bug.verification_status} />
                                </td>
                                {showReported && (
                                    <td className="px-4 py-3 whitespace-nowrap text-ink-secondary tabular">
                                        {formatDate(bug.created_at)}
                                    </td>
                                )}
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
        </>
    );
}
