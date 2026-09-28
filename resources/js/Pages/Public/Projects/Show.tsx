import { ProjectStatusBadge } from '@/Components/BugBadge';
import BugFilterBar from '@/Components/BugFilterBar';
import BugTable from '@/Components/BugTable';
import EmptyState from '@/Components/EmptyState';
import Pagination from '@/Components/Pagination';
import { ProjectImage } from '@/Components/ProjectCard';
import ProjectQRCode from '@/Components/ProjectQRCode';
import PublicLayout from '@/Layouts/PublicLayout';
import { formatDate, formatNumber } from '@/lib/format';
import { routes } from '@/lib/routes';
import { buttonClass } from '@/lib/ui';
import type { Paginated, Project, PublicBug, PublicBugFilters } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { Bug, CalendarDays, CodeXml, ExternalLink, Eye, Lock, MonitorPlay } from 'lucide-react';

interface ProjectShowProps {
    project: Project;
    stats: { total: number; verified: number; fixed: number };
    bugs: Paginated<PublicBug>;
    filters: PublicBugFilters;
    qrCodeUrl: string | null;
}

export default function ProjectShow({ project, stats, bugs, filters, qrCodeUrl }: ProjectShowProps) {
    const acceptsReports = project.accepts_bug_reports;
    const hasFilters = Boolean(filters.search || filters.status || filters.severity || filters.verification);

    return (
        <PublicLayout>
            <Head title={project.name} />

            {project.status === 'draft' && (
                <div className="border-b border-hairline bg-chip">
                    <p className="mx-auto flex max-w-6xl items-center gap-2 px-4 py-2 text-sm text-ink">
                        <Eye className="size-4 text-ink-secondary" aria-hidden="true" />
                        Admin preview — this draft project is not visible to the public yet.
                    </p>
                </div>
            )}

            <section className="border-b border-hairline bg-surface">
                <div className="mx-auto grid max-w-6xl gap-8 px-4 py-8 lg:grid-cols-[1fr_18rem]">
                    <div className="min-w-0">
                        <div className="flex flex-col gap-5 sm:flex-row">
                            <ProjectImage project={project} className="aspect-[16/9] w-full rounded-xl sm:w-56 sm:shrink-0" />
                            <div className="min-w-0">
                                <div className="flex flex-wrap items-center gap-2">
                                    <span className="text-xs font-semibold tracking-wide text-ink-muted uppercase">
                                        {project.project_code}
                                    </span>
                                    <ProjectStatusBadge status={project.status} />
                                </div>
                                <h1 className="mt-1 text-2xl font-bold tracking-tight text-ink sm:text-3xl">{project.name}</h1>
                                {project.presentation_date && (
                                    <p className="mt-2 inline-flex items-center gap-1.5 text-sm text-ink-secondary">
                                        <CalendarDays className="size-4" aria-hidden="true" />
                                        Presented {formatDate(project.presentation_date)}
                                    </p>
                                )}
                            </div>
                        </div>

                        <p className="mt-5 text-base whitespace-pre-line text-ink-secondary">{project.description}</p>

                        {project.technologies.length > 0 && (
                            <div className="mt-4">
                                <h2 className="sr-only">Technology stack</h2>
                                <ul className="flex flex-wrap gap-1.5">
                                    {project.technologies.map((technology) => (
                                        <li key={technology} className="rounded-md bg-chip px-2 py-0.5 text-sm text-ink-secondary">
                                            {technology}
                                        </li>
                                    ))}
                                </ul>
                            </div>
                        )}

                        <div className="mt-6 flex flex-col gap-2 sm:flex-row sm:flex-wrap">
                            {project.demo_url && (
                                <a
                                    href={project.demo_url}
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    className={buttonClass('secondary', 'lg')}
                                >
                                    <MonitorPlay className="size-5" aria-hidden="true" />
                                    Try System
                                    <ExternalLink className="size-4 text-ink-muted" aria-hidden="true" />
                                </a>
                            )}
                            {acceptsReports ? (
                                <Link href={routes.reportBug(project.project_code)} className={buttonClass('primary', 'lg')}>
                                    <Bug className="size-5" aria-hidden="true" />
                                    Report Bug
                                </Link>
                            ) : (
                                <span className="inline-flex items-center gap-2 rounded-lg bg-chip px-4 py-3 text-sm text-ink-secondary">
                                    <Lock className="size-4" aria-hidden="true" />
                                    {project.status === 'closed'
                                        ? 'This project is closed and no longer accepts bug reports.'
                                        : project.status === 'draft'
                                          ? 'Bug reports open when the project is published.'
                                          : 'Bug reporting is turned off for this project at the moment.'}
                                </span>
                            )}
                            {project.repository_url && (
                                <a
                                    href={project.repository_url}
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    className={buttonClass('ghost', 'lg')}
                                >
                                    <CodeXml className="size-5" aria-hidden="true" />
                                    Repository
                                </a>
                            )}
                        </div>

                        <dl className="mt-8 grid grid-cols-3 gap-3">
                            {[
                                { label: 'Total bugs', value: stats.total },
                                { label: 'Verified bugs', value: stats.verified },
                                { label: 'Fixed bugs', value: stats.fixed },
                            ].map((stat) => (
                                <div key={stat.label} className="rounded-xl bg-page px-4 py-3 ring-1 ring-hairline">
                                    <dt className="text-xs text-ink-secondary sm:text-sm">{stat.label}</dt>
                                    <dd className="mt-1 text-2xl font-semibold text-ink">{formatNumber(stat.value)}</dd>
                                </div>
                            ))}
                        </dl>
                    </div>

                    {/* Hidden on phones: the visitor is already on their phone. */}
                    <aside className="hidden rounded-xl bg-page p-5 ring-1 ring-hairline sm:block lg:self-start">
                        <h2 className="mb-4 text-center text-sm font-semibold text-ink">Open on your phone</h2>
                        <ProjectQRCode
                            projectName={project.name}
                            projectCode={project.project_code}
                            targetUrl={project.public_url}
                            svgUrl={qrCodeUrl}
                        />
                    </aside>
                </div>
            </section>

            <section id="bug-board" className="mx-auto max-w-6xl scroll-mt-20 px-4 py-8">
                <div className="flex flex-col gap-1">
                    <h2 className="text-xl font-semibold text-ink">Public bug board</h2>
                    <p className="text-sm text-ink-secondary">
                        Every report is listed here. Reporters stay anonymous; the admin reviews each report and sets its
                        severity, score and status.
                    </p>
                </div>

                <div className="mt-4">
                    <BugFilterBar url={routes.project(project.project_code)} filters={filters} only={['bugs', 'filters']} />
                </div>

                <div className="mt-4 overflow-hidden rounded-xl bg-surface ring-1 ring-hairline">
                    <BugTable
                        bugs={bugs.data}
                        href={(bug) => routes.bug(bug.bug_code)}
                        caption={`Bugs reported for ${project.name}`}
                        empty={
                            <EmptyState
                                icon={Bug}
                                title={hasFilters ? 'No bugs match these filters' : 'No bugs reported yet'}
                                description={
                                    hasFilters
                                        ? 'Try clearing the filters.'
                                        : acceptsReports
                                          ? 'Found something? Be the first to report it.'
                                          : undefined
                                }
                                action={
                                    !hasFilters && acceptsReports ? (
                                        <Link href={routes.reportBug(project.project_code)} className={buttonClass('primary')}>
                                            Report Bug
                                        </Link>
                                    ) : undefined
                                }
                            />
                        }
                    />
                </div>

                <div className="mt-4">
                    <Pagination meta={bugs.meta} only={['bugs', 'filters']} scrollTo="bug-board" />
                </div>
            </section>
        </PublicLayout>
    );
}
