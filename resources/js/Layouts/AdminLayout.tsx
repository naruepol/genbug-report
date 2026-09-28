import FlashMessages from '@/Components/FlashMessages';
import { classNames } from '@/lib/format';
import { routes } from '@/lib/routes';
import { Link, router, usePage } from '@inertiajs/react';
import { Bug, ExternalLink, FolderKanban, LayoutDashboard, ListChecks, LogOut } from 'lucide-react';
import type { ReactNode } from 'react';

interface AdminLayoutProps {
    title: string;
    description?: ReactNode;
    actions?: ReactNode;
    breadcrumbs?: { label: string; href: string }[];
    children: ReactNode;
}

export default function AdminLayout({ title, description, actions, breadcrumbs, children }: AdminLayoutProps) {
    const { props, url } = usePage();
    const user = props.auth.user;

    const navigation = [
        { label: 'Dashboard', href: routes.admin.dashboard(), icon: LayoutDashboard, active: url === '/admin' },
        { label: 'Projects', href: routes.admin.projects(), icon: FolderKanban, active: url.startsWith('/admin/projects') },
        { label: 'Bugs', href: routes.admin.bugs(), icon: ListChecks, active: url.startsWith('/admin/bugs') },
    ];

    return (
        <div className="min-h-screen">
            <header className="sticky top-0 z-30 border-b border-hairline bg-surface">
                <div className="mx-auto flex h-14 max-w-7xl items-center justify-between gap-3 px-4">
                    <div className="flex min-w-0 items-center gap-4">
                        <Link href={routes.admin.dashboard()} className="flex shrink-0 items-center gap-2 font-semibold text-ink">
                            <span className="inline-flex size-8 items-center justify-center rounded-lg bg-brand text-white">
                                <Bug className="size-4.5" aria-hidden="true" />
                            </span>
                            <span className="hidden lg:inline">{props.appName}</span>
                            <span className="rounded-md bg-chip px-1.5 py-0.5 text-xs font-semibold text-ink-secondary">Admin</span>
                        </Link>

                        <nav aria-label="Admin" className="hidden items-center gap-1 md:flex">
                            {navigation.map((item) => (
                                <NavLink key={item.href} {...item} />
                            ))}
                        </nav>
                    </div>

                    <div className="flex items-center gap-2">
                        <Link
                            href={routes.home()}
                            className="hidden items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-sm text-ink-secondary hover:bg-chip hover:text-ink sm:inline-flex"
                        >
                            <ExternalLink className="size-4" aria-hidden="true" />
                            Public site
                        </Link>
                        {user && (
                            <div className="flex items-center gap-2 border-l border-hairline pl-3">
                                {user.avatar_url ? (
                                    <img src={user.avatar_url} alt="" className="size-7 rounded-full" referrerPolicy="no-referrer" />
                                ) : (
                                    <span className="inline-flex size-7 items-center justify-center rounded-full bg-brand-soft text-xs font-semibold text-brand">
                                        {user.name.slice(0, 1).toUpperCase()}
                                    </span>
                                )}
                                <span className="hidden max-w-40 truncate text-sm text-ink xl:inline" title={user.email}>
                                    {user.name}
                                </span>
                                <button
                                    type="button"
                                    onClick={() => router.post(routes.logout())}
                                    className="inline-flex size-8 items-center justify-center rounded-lg text-ink-secondary hover:bg-chip hover:text-ink"
                                    aria-label="Sign out"
                                    title="Sign out"
                                >
                                    <LogOut className="size-4" />
                                </button>
                            </div>
                        )}
                    </div>
                </div>

                <nav aria-label="Admin" className="flex gap-1 overflow-x-auto border-t border-hairline px-3 py-1.5 md:hidden">
                    {navigation.map((item) => (
                        <NavLink key={item.href} {...item} />
                    ))}
                </nav>
            </header>

            <FlashMessages />

            <main className="mx-auto max-w-7xl px-4 py-6 lg:py-8">
                {breadcrumbs && breadcrumbs.length > 0 && (
                    <nav aria-label="Breadcrumb" className="mb-2">
                        <ol className="flex flex-wrap items-center gap-1 text-sm text-ink-secondary">
                            {breadcrumbs.map((crumb) => (
                                <li key={crumb.href} className="flex items-center gap-1">
                                    <Link href={crumb.href} className="hover:text-ink hover:underline">
                                        {crumb.label}
                                    </Link>
                                    <span aria-hidden="true">/</span>
                                </li>
                            ))}
                        </ol>
                    </nav>
                )}

                <div className="mb-6 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div className="min-w-0">
                        <h1 className="text-2xl font-semibold text-ink">{title}</h1>
                        {description && <div className="mt-1 text-sm text-ink-secondary">{description}</div>}
                    </div>
                    {actions && <div className="flex shrink-0 flex-wrap gap-2">{actions}</div>}
                </div>

                {children}
            </main>
        </div>
    );
}

function NavLink({ label, href, icon: Icon, active }: { label: string; href: string; icon: typeof Bug; active: boolean }) {
    return (
        <Link
            href={href}
            aria-current={active ? 'page' : undefined}
            className={classNames(
                'inline-flex shrink-0 items-center gap-1.5 rounded-lg px-3 py-1.5 text-sm font-medium',
                active ? 'bg-chip text-ink' : 'text-ink-secondary hover:bg-chip hover:text-ink',
            )}
        >
            <Icon className="size-4" aria-hidden="true" />
            {label}
        </Link>
    );
}
