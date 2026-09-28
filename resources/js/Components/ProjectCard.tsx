import { ProjectStatusBadge } from '@/Components/BugBadge';
import { classNames, formatNumber } from '@/lib/format';
import { routes } from '@/lib/routes';
import type { Project } from '@/types';
import { Link } from '@inertiajs/react';
import { Lock } from 'lucide-react';

export function ProjectImage({ project, className }: { project: Pick<Project, 'name' | 'image_url'>; className?: string }) {
    if (project.image_url) {
        return <img src={project.image_url} alt="" loading="lazy" className={classNames('bg-chip object-cover', className)} />;
    }

    const initials = project.name
        .split(/\s+/)
        .filter(Boolean)
        .slice(0, 2)
        .map((word) => word[0]?.toUpperCase())
        .join('');

    return (
        <div
            aria-hidden="true"
            className={classNames(
                'flex items-center justify-center bg-gradient-to-br from-brand-soft to-chip text-3xl font-semibold text-brand',
                className,
            )}
        >
            {initials}
        </div>
    );
}

export default function ProjectCard({ project }: { project: Project }) {
    const stats = [
        { label: 'Bugs', value: project.bugs_count },
        { label: 'Verified', value: project.verified_bugs_count },
        { label: 'Fixed', value: project.fixed_bugs_count },
    ].filter((stat) => stat.value !== undefined);

    return (
        <article className="group relative flex flex-col overflow-hidden rounded-xl bg-surface ring-1 ring-hairline transition hover:shadow-md hover:ring-baseline">
            <ProjectImage project={project} className="aspect-[16/9] w-full" />

            <div className="flex flex-1 flex-col gap-3 p-4">
                <div className="flex items-start justify-between gap-3">
                    <div className="min-w-0">
                        <p className="text-xs font-semibold tracking-wide text-ink-muted uppercase">{project.project_code}</p>
                        <h3 className="mt-0.5 text-base font-semibold text-ink">
                            <Link href={routes.project(project.project_code)} className="after:absolute after:inset-0">
                                {project.name}
                            </Link>
                        </h3>
                    </div>
                    <ProjectStatusBadge status={project.status} />
                </div>

                <p className="line-clamp-2 text-sm text-ink-secondary">{project.description}</p>

                {project.status === 'published' && !project.bug_reporting_enabled && (
                    <p className="flex items-center gap-1.5 text-xs text-ink-secondary">
                        <Lock className="size-3.5" aria-hidden="true" />
                        Bug reporting is off at the moment
                    </p>
                )}

                {project.technologies.length > 0 && (
                    <ul className="flex flex-wrap gap-1.5" aria-label="Technology stack">
                        {project.technologies.slice(0, 5).map((technology) => (
                            <li key={technology} className="rounded-md bg-chip px-1.5 py-0.5 text-xs text-ink-secondary">
                                {technology}
                            </li>
                        ))}
                    </ul>
                )}

                {stats.length > 0 && (
                    <dl className="mt-auto grid grid-cols-3 gap-2 border-t border-hairline pt-3 text-center">
                        {stats.map((stat) => (
                            <div key={stat.label}>
                                <dt className="text-xs text-ink-secondary">{stat.label}</dt>
                                <dd className="text-lg font-semibold text-ink">{formatNumber(stat.value ?? 0)}</dd>
                            </div>
                        ))}
                    </dl>
                )}
            </div>
        </article>
    );
}
