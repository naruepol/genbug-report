import EmptyState from '@/Components/EmptyState';
import { TextInput } from '@/Components/Form';
import Pagination from '@/Components/Pagination';
import ProjectCard from '@/Components/ProjectCard';
import PublicLayout from '@/Layouts/PublicLayout';
import { routes } from '@/lib/routes';
import type { Paginated, Project } from '@/types';
import { Head, router } from '@inertiajs/react';
import { Search } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';

interface ProjectsIndexProps {
    projects: Paginated<Project>;
    filters: { search: string };
}

export default function ProjectsIndex({ projects, filters }: ProjectsIndexProps) {
    const [search, setSearch] = useState(filters.search);
    const firstRender = useRef(true);

    useEffect(() => {
        if (firstRender.current) {
            firstRender.current = false;
            return;
        }

        const timer = window.setTimeout(() => {
            router.get(routes.projects(), search ? { search } : {}, {
                preserveState: true,
                preserveScroll: true,
                replace: true,
                only: ['projects', 'filters'],
            });
        }, 350);

        return () => window.clearTimeout(timer);
    }, [search]);

    return (
        <PublicLayout>
            <Head title="Projects" />

            <div className="mx-auto max-w-6xl px-4 py-8">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold text-ink">Projects</h1>
                        <p className="mt-1 text-sm text-ink-secondary">Pick a project to try its demo and report bugs.</p>
                    </div>
                    <div className="relative sm:w-72">
                        <label htmlFor="project-search" className="sr-only">
                            Search projects
                        </label>
                        <Search
                            className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-ink-muted"
                            aria-hidden="true"
                        />
                        <TextInput
                            id="project-search"
                            type="search"
                            value={search}
                            onChange={(event) => setSearch(event.target.value)}
                            placeholder="Search by name, code or technology"
                            className="pl-9"
                        />
                    </div>
                </div>

                {projects.data.length === 0 ? (
                    <div className="mt-6 rounded-xl bg-surface ring-1 ring-hairline">
                        <EmptyState
                            title={filters.search ? 'No projects match your search' : 'No projects yet'}
                            description={filters.search ? 'Try another name or code.' : 'Projects appear here once they are published.'}
                        />
                    </div>
                ) : (
                    <div className="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        {projects.data.map((project) => (
                            <ProjectCard key={project.id} project={project} />
                        ))}
                    </div>
                )}

                <div className="mt-6">
                    <Pagination meta={projects.meta} />
                </div>
            </div>
        </PublicLayout>
    );
}
