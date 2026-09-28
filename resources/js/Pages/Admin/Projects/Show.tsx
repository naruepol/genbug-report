import { Badge, ProjectStatusBadge } from '@/Components/BugBadge';
import BugReportingToggle, { bugReportingSummary } from '@/Components/BugReportingToggle';
import BugTable from '@/Components/BugTable';
import EmptyState from '@/Components/EmptyState';
import HorizontalBarChart from '@/Components/HorizontalBarChart';
import { ProjectImage } from '@/Components/ProjectCard';
import ProjectQRCode from '@/Components/ProjectQRCode';
import StatCard from '@/Components/StatCard';
import AdminLayout from '@/Layouts/AdminLayout';
import { formatDate } from '@/lib/format';
import { routes } from '@/lib/routes';
import { buttonClass, cardClass } from '@/lib/ui';
import type { AdminBug, BreakdownRow, BugSummary, Project } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { CircleCheck, CodeXml, ExternalLink, Lock, MonitorPlay, Pencil, Trash } from 'lucide-react';
import { useState, type ReactNode } from 'react';

interface ProjectShowProps {
    project: Project;
    stats: BugSummary;
    charts: { status: BreakdownRow[]; severity: BreakdownRow[]; category: BreakdownRow[] };
    recentBugs: AdminBug[];
    qrCode: { svg: string | null; png: string | null; target: string };
}

export default function ProjectShow({ project, stats, charts, recentBugs, qrCode }: ProjectShowProps) {
    const [busy, setBusy] = useState<string | null>(null);

    function post(action: string, url: string) {
        router.post(url, {}, { preserveScroll: true, onStart: () => setBusy(action), onFinish: () => setBusy(null) });
    }

    function destroy() {
        const confirmed = window.confirm(
            `Delete ${project.name} (${project.project_code})?\n\nAll of its ${stats.total} bug reports and screenshots will be deleted too. This cannot be undone.`,
        );

        if (confirmed) {
            router.delete(routes.admin.project(project.id), { onStart: () => setBusy('delete'), onFinish: () => setBusy(null) });
        }
    }

    const projectBugs = (params: Record<string, string> = {}) => routes.admin.bugs({ project: project.id, ...params });

    return (
        <AdminLayout
            title={project.name}
            description={
                <span className="flex flex-wrap items-center gap-2">
                    <span className="font-mono">{project.project_code}</span>
                    <ProjectStatusBadge status={project.status} />
                    {!project.bug_reporting_enabled && <Badge label="Bug reporting off" tone="muted" />}
                </span>
            }
            breadcrumbs={[{ label: 'Projects', href: routes.admin.projects() }]}
            actions={
                <>
                    {project.status !== 'published' && (
                        <button
                            type="button"
                            onClick={() => post('publish', routes.admin.publishProject(project.id))}
                            disabled={busy !== null}
                            className={buttonClass('primary')}
                        >
                            <CircleCheck className="size-4" aria-hidden="true" />
                            Publish
                        </button>
                    )}
                    {project.status === 'published' && (
                        <button
                            type="button"
                            onClick={() => post('close', routes.admin.closeProject(project.id))}
                            disabled={busy !== null}
                            className={buttonClass('secondary')}
                        >
                            <Lock className="size-4" aria-hidden="true" />
                            Close
                        </button>
                    )}
                    <Link href={routes.admin.editProject(project.id)} className={buttonClass('secondary')}>
                        <Pencil className="size-4" aria-hidden="true" />
                        Edit
                    </Link>
                    <a href={routes.project(project.project_code)} className={buttonClass('secondary')} target="_blank" rel="noreferrer">
                        <ExternalLink className="size-4" aria-hidden="true" />
                        Public page
                    </a>
                </>
            }
        >
            <Head title={project.name} />

            <div className="grid gap-6 xl:grid-cols-[1fr_20rem]">
                <div className="min-w-0 space-y-6">
                    <section aria-labelledby="summary-heading">
                        <h2 id="summary-heading" className="sr-only">
                            Bug summary
                        </h2>
                        <div className="grid grid-cols-2 gap-3 sm:grid-cols-4 lg:grid-cols-7">
                            <StatCard label="Total bugs" value={stats.total} href={projectBugs()} />
                            <StatCard label="Pending" value={stats.verification.pending} tone="warning" href={projectBugs({ verification: 'pending' })} />
                            <StatCard label="Verified" value={stats.verification.verified} tone="good" href={projectBugs({ verification: 'verified' })} />
                            <StatCard label="Open" value={stats.status.open} tone="info" href={projectBugs({ status: 'open' })} />
                            <StatCard label="In progress" value={stats.status.in_progress} tone="warning" href={projectBugs({ status: 'in_progress' })} />
                            <StatCard label="Fixed" value={stats.status.fixed} tone="good" href={projectBugs({ status: 'fixed' })} />
                            <StatCard label="Closed" value={stats.status.closed} tone="neutral" href={projectBugs({ status: 'closed' })} />
                        </div>
                    </section>

                    <section aria-label="Charts" className="grid gap-4 lg:grid-cols-3">
                        <HorizontalBarChart title="Bug by Status" rows={charts.status} />
                        <HorizontalBarChart title="Bug by Severity" rows={charts.severity} />
                        <HorizontalBarChart title="Bug by Category" rows={charts.category} />
                    </section>

                    <section className={`${cardClass} overflow-hidden`}>
                        <div className="flex items-center justify-between gap-3 border-b border-hairline px-4 py-3">
                            <h2 className="text-sm font-semibold text-ink">Latest bug reports</h2>
                            <Link href={projectBugs()} className="text-sm font-medium text-link hover:underline">
                                View all bugs
                            </Link>
                        </div>
                        <BugTable
                            bugs={recentBugs}
                            href={(bug) => routes.admin.bug(bug.id)}
                            showPriority
                            showReported
                            caption={`Latest bug reports for ${project.name}`}
                            empty={<EmptyState title="No bug reports yet" description="Share the QR code after your presentation." />}
                        />
                    </section>
                </div>

                <aside className="space-y-6">
                    <section className={`${cardClass} p-5`} aria-labelledby="bug-reporting-heading">
                        <div className="flex items-start justify-between gap-4">
                            <div>
                                <h2 id="bug-reporting-heading" className="text-sm font-semibold text-ink">
                                    Bug reporting
                                </h2>
                                <p className="mt-1 text-sm text-ink-secondary">{bugReportingSummary(project)}</p>
                            </div>
                            <BugReportingToggle project={project} />
                        </div>
                    </section>

                    <section className={`${cardClass} p-5`}>
                        <h2 className="mb-4 text-sm font-semibold text-ink">QR code</h2>
                        <ProjectQRCode
                            projectName={project.name}
                            projectCode={project.project_code}
                            targetUrl={qrCode.target}
                            svgUrl={qrCode.svg}
                            downloads={{
                                png: routes.admin.downloadQrCode(project.id, 'png'),
                                svg: routes.admin.downloadQrCode(project.id, 'svg'),
                            }}
                            onGenerate={() => post('qr', routes.admin.generateQrCode(project.id))}
                            generating={busy === 'qr'}
                            note={
                                project.status === 'draft'
                                    ? 'The link works for the public once the project is published.'
                                    : 'Use it in slides, posters or on the presentation screen.'
                            }
                        />
                    </section>

                    <section className={`${cardClass} overflow-hidden`}>
                        <ProjectImage project={project} className="aspect-[16/9] w-full" />
                        <dl className="space-y-3 p-5 text-sm">
                            <Detail label="Description">
                                <p className="whitespace-pre-line text-ink-secondary">{project.description}</p>
                            </Detail>
                            <Detail label="Demo URL">
                                {project.demo_url ? (
                                    <a href={project.demo_url} target="_blank" rel="noopener noreferrer" className="inline-flex items-center gap-1 break-all text-link hover:underline">
                                        <MonitorPlay className="size-4 shrink-0" aria-hidden="true" />
                                        {project.demo_url}
                                    </a>
                                ) : (
                                    <span className="text-ink-muted">Not set</span>
                                )}
                            </Detail>
                            <Detail label="Repository URL">
                                {project.repository_url ? (
                                    <a href={project.repository_url} target="_blank" rel="noopener noreferrer" className="inline-flex items-center gap-1 break-all text-link hover:underline">
                                        <CodeXml className="size-4 shrink-0" aria-hidden="true" />
                                        {project.repository_url}
                                    </a>
                                ) : (
                                    <span className="text-ink-muted">Not set</span>
                                )}
                            </Detail>
                            <Detail label="Technology stack">
                                {project.technologies.length > 0 ? project.technologies.join(', ') : <span className="text-ink-muted">Not set</span>}
                            </Detail>
                            <Detail label="Presentation date">{formatDate(project.presentation_date)}</Detail>
                        </dl>
                    </section>

                    <section className={`${cardClass} p-5`}>
                        <h2 className="text-sm font-semibold text-ink">Delete project</h2>
                        <p className="mt-1 text-sm text-ink-secondary">Removes the project, all of its bug reports and screenshots.</p>
                        <button type="button" onClick={destroy} disabled={busy !== null} className={buttonClass('danger', 'md', 'mt-3')}>
                            <Trash className="size-4" aria-hidden="true" />
                            Delete project
                        </button>
                    </section>
                </aside>
            </div>
        </AdminLayout>
    );
}

function Detail({ label, children }: { label: string; children: ReactNode }) {
    return (
        <div>
            <dt className="text-xs font-medium text-ink-secondary">{label}</dt>
            <dd className="mt-0.5 text-ink">{children}</dd>
        </div>
    );
}
