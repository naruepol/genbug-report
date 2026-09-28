import { CategoryLabel, PriorityBadge, ScoreBadge, SeverityBadge, StatusBadge, VerificationBadge } from '@/Components/BugBadge';
import PublicLayout from '@/Layouts/PublicLayout';
import { formatDateTime } from '@/lib/format';
import { routes } from '@/lib/routes';
import { buttonClass, cardClass } from '@/lib/ui';
import type { PublicBug } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, Bug, UserRound } from 'lucide-react';
import type { ReactNode } from 'react';

interface BugShowProps {
    bug: PublicBug;
}

export default function BugShow({ bug }: BugShowProps) {
    const project = bug.project;

    return (
        <PublicLayout>
            <Head title={`${bug.bug_code} · ${bug.title}`} />

            <div className="mx-auto max-w-4xl px-4 py-6 sm:py-10">
                {project && (
                    <Link
                        href={routes.project(project.project_code)}
                        className="inline-flex items-center gap-1.5 text-sm text-ink-secondary hover:text-ink"
                    >
                        <ArrowLeft className="size-4" aria-hidden="true" />
                        {project.name}
                    </Link>
                )}

                <header className="mt-3">
                    <div className="flex flex-wrap items-center gap-2">
                        <span className="text-sm font-semibold text-ink-secondary tabular">{bug.bug_code}</span>
                        <CategoryLabel category={bug.category} />
                    </div>
                    <h1 className="mt-1 text-2xl font-bold tracking-tight break-words text-ink sm:text-3xl">{bug.title}</h1>
                    <p className="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-ink-secondary">
                        <span className="inline-flex items-center gap-1.5">
                            <UserRound className="size-4" aria-hidden="true" />
                            Reported by <span className="font-medium text-ink">Anonymous User</span>
                        </span>
                        <span>Created {formatDateTime(bug.created_at)}</span>
                        <span>Updated {formatDateTime(bug.updated_at)}</span>
                    </p>
                </header>

                <dl className={`${cardClass} mt-6 grid grid-cols-2 gap-x-4 gap-y-4 p-4 sm:grid-cols-5 sm:p-5`}>
                    <Meta label="Status">
                        <StatusBadge status={bug.status} />
                    </Meta>
                    <Meta label="Verification">
                        <VerificationBadge status={bug.verification_status} />
                    </Meta>
                    <Meta label="Severity">
                        <SeverityBadge severity={bug.severity} />
                    </Meta>
                    <Meta label="Priority">
                        <PriorityBadge priority={bug.priority} />
                    </Meta>
                    <Meta label="Score">
                        <ScoreBadge score={bug.score} />
                    </Meta>
                </dl>

                <div className="mt-6 space-y-4">
                    <TextSection title="Description" text={bug.description} />
                    <TextSection title="Steps to reproduce" text={bug.steps_to_reproduce} />
                    <div className="grid gap-4 sm:grid-cols-2">
                        <TextSection title="Expected result" text={bug.expected_result} />
                        <TextSection title="Actual result" text={bug.actual_result} />
                    </div>

                    {bug.screenshots && bug.screenshots.length > 0 && (
                        <section className={`${cardClass} p-4 sm:p-5`}>
                            <h2 className="text-sm font-semibold text-ink">Screenshot</h2>
                            <div className="mt-3 grid gap-3 sm:grid-cols-2">
                                {bug.screenshots.map((screenshot, index) => (
                                    <a
                                        key={screenshot.id}
                                        href={screenshot.url}
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        className="block overflow-hidden rounded-lg ring-1 ring-hairline hover:ring-brand"
                                    >
                                        <img
                                            src={screenshot.url}
                                            alt={`Screenshot ${index + 1} for ${bug.bug_code}`}
                                            loading="lazy"
                                            className="max-h-96 w-full bg-chip object-contain"
                                        />
                                    </a>
                                ))}
                            </div>
                        </section>
                    )}
                </div>

                {project?.accepts_bug_reports && (
                    <div className="mt-8 flex flex-col gap-2 sm:flex-row">
                        <Link href={routes.reportBug(project.project_code)} className={buttonClass('primary')}>
                            <Bug className="size-4" aria-hidden="true" />
                            Report another bug
                        </Link>
                        <Link href={`${routes.project(project.project_code)}#bug-board`} className={buttonClass('secondary')}>
                            Back to the bug board
                        </Link>
                    </div>
                )}
            </div>
        </PublicLayout>
    );
}

function Meta({ label, children }: { label: string; children: ReactNode }) {
    return (
        <div>
            <dt className="text-xs text-ink-secondary">{label}</dt>
            <dd className="mt-1">{children}</dd>
        </div>
    );
}

function TextSection({ title, text }: { title: string; text: string | null }) {
    if (!text) {
        return null;
    }

    return (
        <section className={`${cardClass} p-4 sm:p-5`}>
            <h2 className="text-sm font-semibold text-ink">{title}</h2>
            <p className="mt-2 text-sm break-words whitespace-pre-line text-ink-secondary">{text}</p>
        </section>
    );
}
