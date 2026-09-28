import { ProjectStatusBadge } from '@/Components/BugBadge';
import BugReportingToggle from '@/Components/BugReportingToggle';
import EmptyState from '@/Components/EmptyState';
import { Select, TextInput } from '@/Components/Form';
import Pagination from '@/Components/Pagination';
import { ProjectImage } from '@/Components/ProjectCard';
import AdminLayout from '@/Layouts/AdminLayout';
import { PROJECT_STATUSES } from '@/lib/enums';
import { formatDate, formatNumber } from '@/lib/format';
import { routes } from '@/lib/routes';
import { buttonClass, cardClass } from '@/lib/ui';
import type { Paginated, Project, ProjectStatus } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { FolderKanban, Plus, Search } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';

interface ProjectsIndexProps {
    projects: Paginated<Project>;
    filters: { search: string; status: ProjectStatus | null };
}

export default function ProjectsIndex({ projects, filters }: ProjectsIndexProps) {
    const [search, setSearch] = useState(filters.search);
    const [status, setStatus] = useState<string>(filters.status ?? '');
    const firstRender = useRef(true);

    function apply(next: { search: string; status: string }) {
        const query = Object.fromEntries(Object.entries(next).filter(([, value]) => value !== ''));

        router.get(routes.admin.projects(), query, { preserveState: true, preserveScroll: true, replace: true });
    }

    useEffect(() => {
        if (firstRender.current) {
            firstRender.current = false;
            return;
        }

        const timer = window.setTimeout(() => apply({ search, status }), 350);

        return () => window.clearTimeout(timer);
    }, [search]);

    return (
        <AdminLayout
            title="Projects"
            description="Create projects, publish them for the public and share their QR codes."
            actions={
                <Link href={routes.admin.createProject()} className={buttonClass('primary')}>
                    <Plus className="size-4" aria-hidden="true" />
                    New project
                </Link>
            }
        >
            <Head title="Projects" />

            <div className="mb-4 flex flex-col gap-2 sm:flex-row">
                <div className="relative sm:w-72">
                    <label htmlFor="project-search" className="sr-only">
                        Search projects
                    </label>
                    <Search className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-ink-muted" aria-hidden="true" />
                    <TextInput
                        id="project-search"
                        type="search"
                        value={search}
                        onChange={(event) => setSearch(event.target.value)}
                        placeholder="Search name or code"
                        className="pl-9"
                    />
                </div>
                <Select
                    aria-label="Status"
                    value={status}
                    onChange={(event) => {
                        setStatus(event.target.value);
                        apply({ search, status: event.target.value });
                    }}
                    className="sm:w-44"
                >
                    <option value="">All statuses</option>
                    {PROJECT_STATUSES.map((option) => (
                        <option key={option.value} value={option.value}>
                            {option.label}
                        </option>
                    ))}
                </Select>
            </div>

            <div className={`${cardClass} overflow-hidden`}>
                {projects.data.length === 0 ? (
                    <EmptyState
                        icon={FolderKanban}
                        title={filters.search || filters.status ? 'No projects match these filters' : 'No projects yet'}
                        description="Create a project, add its demo URL and publish it to share the QR code."
                        action={
                            <Link href={routes.admin.createProject()} className={buttonClass('primary')}>
                                New project
                            </Link>
                        }
                    />
                ) : (
                    <ul className="divide-y divide-hairline">
                        {projects.data.map((project) => (
                            <li key={project.id} className="relative flex flex-col gap-3 p-4 hover:bg-page sm:flex-row sm:items-center">
                                <ProjectImage project={project} className="hidden aspect-[16/9] w-28 shrink-0 rounded-lg text-lg sm:flex" />
                                <div className="min-w-0 flex-1">
                                    <div className="flex flex-wrap items-center gap-2">
                                        <Link
                                            href={routes.admin.project(project.id)}
                                            className="font-semibold text-ink after:absolute after:inset-0 hover:underline"
                                        >
                                            {project.name}
                                        </Link>
                                        <ProjectStatusBadge status={project.status} />
                                    </div>
                                    <p className="mt-0.5 text-xs text-ink-secondary">
                                        <span className="font-mono">{project.project_code}</span>
                                        {project.presentation_date && <> · Presented {formatDate(project.presentation_date)}</>}
                                    </p>
                                </div>
                                <dl className="flex gap-6 text-sm sm:text-right">
                                    <div>
                                        <dt className="text-xs text-ink-secondary">Bugs</dt>
                                        <dd className="font-semibold text-ink">{formatNumber(project.bugs_count ?? 0)}</dd>
                                    </div>
                                    <div>
                                        <dt className="text-xs text-ink-secondary">Pending</dt>
                                        <dd className="font-semibold text-ink">{formatNumber(project.pending_bugs_count ?? 0)}</dd>
                                    </div>
                                    <div>
                                        <dt className="text-xs text-ink-secondary">Fixed</dt>
                                        <dd className="font-semibold text-ink">{formatNumber(project.fixed_bugs_count ?? 0)}</dd>
                                    </div>
                                </dl>
                                {/* Above the row's full-size link so the switch stays clickable. */}
                                <div className="relative z-10 flex items-center gap-2 sm:w-36 sm:justify-end">
                                    <BugReportingToggle project={project} size="sm" />
                                    <span className="w-16 text-xs text-ink-secondary sm:w-auto" aria-hidden="true">
                                        {project.bug_reporting_enabled ? 'Reports on' : 'Reports off'}
                                    </span>
                                </div>
                            </li>
                        ))}
                    </ul>
                )}
            </div>

            <div className="mt-4">
                <Pagination meta={projects.meta} />
            </div>
        </AdminLayout>
    );
}
