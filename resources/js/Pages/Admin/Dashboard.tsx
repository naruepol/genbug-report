import ActivityList from '@/Components/ActivityList';
import { ProjectStatusBadge } from '@/Components/BugBadge';
import BugTable from '@/Components/BugTable';
import EmptyState from '@/Components/EmptyState';
import StatCard from '@/Components/StatCard';
import AdminLayout from '@/Layouts/AdminLayout';
import { formatNumber, pluralize } from '@/lib/format';
import { routes } from '@/lib/routes';
import { buttonClass, cardClass } from '@/lib/ui';
import type { AdminBug, AuditEntry, BugSummary, Project } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import type { ReactNode } from 'react';

interface DashboardProps {
    stats: {
        projects: { total: number; published: number };
        bugs: BugSummary;
    };
    projects: Project[];
    recentBugs: AdminBug[];
    activity: AuditEntry[];
}

export default function Dashboard({ stats, projects, recentBugs, activity }: DashboardProps) {
    const bugs = stats.bugs;

    return (
        <AdminLayout
            title="Dashboard"
            description="Overview of projects and bug reports."
            actions={
                <Link href={routes.admin.createProject()} className={buttonClass('primary')}>
                    <Plus className="size-4" aria-hidden="true" />
                    New project
                </Link>
            }
        >
            <Head title="Admin dashboard" />

            <section aria-labelledby="stats-heading">
                <h2 id="stats-heading" className="sr-only">
                    Summary
                </h2>
                <div className="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
                    <StatCard label="Total projects" value={stats.projects.total} href={routes.admin.projects()} />
                    <StatCard
                        label="Published projects"
                        value={stats.projects.published}
                        tone="good"
                        href={`${routes.admin.projects()}?status=published`}
                    />
                    <StatCard label="Total bugs" value={bugs.total} href={routes.admin.bugs()} />
                    <StatCard
                        label="Pending bugs"
                        value={bugs.verification.pending}
                        tone="warning"
                        hint="Waiting for review"
                        href={routes.admin.bugs({ verification: 'pending' })}
                    />
                    <StatCard
                        label="Verified bugs"
                        value={bugs.verification.verified}
                        tone="good"
                        href={routes.admin.bugs({ verification: 'verified' })}
                    />
                    <StatCard label="Open bugs" value={bugs.status.open} tone="info" href={routes.admin.bugs({ status: 'open' })} />
                    <StatCard
                        label="In progress bugs"
                        value={bugs.status.in_progress}
                        tone="warning"
                        href={routes.admin.bugs({ status: 'in_progress' })}
                    />
                    <StatCard label="Fixed bugs" value={bugs.status.fixed} tone="good" href={routes.admin.bugs({ status: 'fixed' })} />
                    <StatCard
                        label="Closed bugs"
                        value={bugs.status.closed}
                        tone="neutral"
                        href={routes.admin.bugs({ status: 'closed' })}
                    />
                </div>
            </section>

            <div className="mt-6 space-y-6">
                <Panel
                    title="Latest bug reports"
                    action={
                        <Link href={routes.admin.bugs()} className="text-sm font-medium text-link hover:underline">
                            View all
                        </Link>
                    }
                >
                    <BugTable
                        bugs={recentBugs}
                        href={(bug) => routes.admin.bug(bug.id)}
                        showProject
                        showReported
                        caption="Latest bug reports"
                        empty={<EmptyState title="No bug reports yet" description="Reports from the public will appear here." />}
                    />
                </Panel>

                <div className="grid gap-6 lg:grid-cols-2">
                    <Panel
                        title="Projects"
                        action={
                            <Link href={routes.admin.projects()} className="text-sm font-medium text-link hover:underline">
                                Manage
                            </Link>
                        }
                    >
                        {projects.length === 0 ? (
                            <EmptyState
                                title="No projects yet"
                                action={
                                    <Link href={routes.admin.createProject()} className={buttonClass('primary', 'sm')}>
                                        Create a project
                                    </Link>
                                }
                            />
                        ) : (
                            <ul className="divide-y divide-hairline">
                                {projects.map((project) => (
                                    <li key={project.id} className="relative flex items-center justify-between gap-3 px-4 py-3 hover:bg-page">
                                        <div className="min-w-0">
                                            <Link
                                                href={routes.admin.project(project.id)}
                                                className="block truncate text-sm font-medium text-ink after:absolute after:inset-0"
                                            >
                                                {project.name}
                                            </Link>
                                            <p className="mt-0.5 text-xs text-ink-secondary">
                                                {pluralize(project.bugs_count ?? 0, 'bug')} ·{' '}
                                                {formatNumber(project.pending_bugs_count ?? 0)} pending
                                            </p>
                                        </div>
                                        <ProjectStatusBadge status={project.status} />
                                    </li>
                                ))}
                            </ul>
                        )}
                    </Panel>

                    <Panel title="Recent activity">
                        <ActivityList entries={activity} />
                    </Panel>
                </div>
            </div>
        </AdminLayout>
    );
}

function Panel({ title, action, children }: { title: string; action?: ReactNode; children: ReactNode }) {
    return (
        <section className={`${cardClass} overflow-hidden`}>
            <div className="flex items-center justify-between gap-3 border-b border-hairline px-4 py-3">
                <h2 className="text-sm font-semibold text-ink">{title}</h2>
                {action}
            </div>
            {children}
        </section>
    );
}
