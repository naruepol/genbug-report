import EmptyState from '@/Components/EmptyState';
import ProjectCard from '@/Components/ProjectCard';
import PublicLayout from '@/Layouts/PublicLayout';
import { routes } from '@/lib/routes';
import { buttonClass } from '@/lib/ui';
import type { Project } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { ArrowRight, Bug, MonitorSmartphone, ScanQrCode } from 'lucide-react';

interface HomeProps {
    projects: Project[];
}

const steps = [
    {
        icon: ScanQrCode,
        title: 'Scan the QR code',
        text: 'Open a project from the QR code on the presentation screen, or pick one from the list.',
    },
    {
        icon: MonitorSmartphone,
        title: 'Try the system',
        text: 'Use the demo on your phone or laptop and look for anything that does not work as expected.',
    },
    {
        icon: Bug,
        title: 'Report a bug',
        text: 'Describe what happened, add a screenshot if you can. You stay anonymous on the public board.',
    },
];

export default function Home({ projects }: HomeProps) {
    return (
        <PublicLayout>
            <Head title="Try projects and report bugs" />

            <section className="border-b border-hairline bg-surface">
                <div className="mx-auto max-w-6xl px-4 py-12 sm:py-16">
                    <div className="max-w-2xl">
                        <p className="text-sm font-semibold text-link">Public bug reporting</p>
                        <h1 className="mt-2 text-3xl font-bold tracking-tight text-ink sm:text-4xl">
                            Try the projects. Report the bugs you find.
                        </h1>
                        <p className="mt-4 text-base text-ink-secondary sm:text-lg">
                            After each presentation, the project is open for everyone to test. Every report helps the team make
                            it better — no account needed.
                        </p>
                        <div className="mt-6 flex flex-wrap gap-3">
                            <Link href={routes.projects()} className={buttonClass('primary', 'lg')}>
                                Browse projects
                                <ArrowRight className="size-4" aria-hidden="true" />
                            </Link>
                        </div>
                    </div>

                    <ol className="mt-10 grid gap-4 sm:grid-cols-3">
                        {steps.map((step, index) => (
                            <li key={step.title} className="rounded-xl bg-page p-4 ring-1 ring-hairline">
                                <div className="flex items-center gap-3">
                                    <span className="inline-flex size-9 items-center justify-center rounded-lg bg-brand-soft text-brand">
                                        <step.icon className="size-5" aria-hidden="true" />
                                    </span>
                                    <span className="text-xs font-semibold text-ink-muted">Step {index + 1}</span>
                                </div>
                                <h2 className="mt-3 font-semibold text-ink">{step.title}</h2>
                                <p className="mt-1 text-sm text-ink-secondary">{step.text}</p>
                            </li>
                        ))}
                    </ol>
                </div>
            </section>

            <section className="mx-auto max-w-6xl px-4 py-10">
                <div className="flex items-end justify-between gap-4">
                    <h2 className="text-xl font-semibold text-ink">Projects</h2>
                    <Link href={routes.projects()} className="text-sm font-medium text-link hover:underline">
                        View all
                    </Link>
                </div>

                {projects.length === 0 ? (
                    <div className="mt-4 rounded-xl bg-surface ring-1 ring-hairline">
                        <EmptyState title="No projects yet" description="Projects appear here once they are published." />
                    </div>
                ) : (
                    <div className="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        {projects.map((project) => (
                            <ProjectCard key={project.id} project={project} />
                        ))}
                    </div>
                )}
            </section>
        </PublicLayout>
    );
}
