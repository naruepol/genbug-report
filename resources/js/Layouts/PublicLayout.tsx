import FlashMessages from '@/Components/FlashMessages';
import { classNames } from '@/lib/format';
import { routes } from '@/lib/routes';
import { Link, usePage } from '@inertiajs/react';
import { Bug, LayoutDashboard } from 'lucide-react';
import type { ReactNode } from 'react';

export default function PublicLayout({ children }: { children: ReactNode }) {
    const { props, url } = usePage();
    const user = props.auth.user;

    const navigation = [
        { label: 'Home', href: routes.home(), active: url === '/' },
        { label: 'Projects', href: routes.projects(), active: url.startsWith('/projects') || url.startsWith('/project/') },
    ];

    return (
        <div className="flex min-h-screen flex-col">
            <header className="sticky top-0 z-30 border-b border-hairline bg-surface/90 backdrop-blur">
                <div className="mx-auto flex h-14 max-w-6xl items-center justify-between gap-4 px-4">
                    <Link href={routes.home()} className="flex items-center gap-2 font-semibold text-ink">
                        <span className="inline-flex size-8 items-center justify-center rounded-lg bg-brand text-white">
                            <Bug className="size-4.5" aria-hidden="true" />
                        </span>
                        <span className="hidden sm:inline">{props.appName}</span>
                    </Link>

                    <nav aria-label="Main" className="flex items-center gap-1">
                        {navigation.map((item) => (
                            <Link
                                key={item.href}
                                href={item.href}
                                aria-current={item.active ? 'page' : undefined}
                                className={classNames(
                                    'rounded-lg px-3 py-1.5 text-sm font-medium',
                                    item.active ? 'bg-chip text-ink' : 'text-ink-secondary hover:bg-chip hover:text-ink',
                                )}
                            >
                                {item.label}
                            </Link>
                        ))}
                        {user?.is_admin && (
                            <Link
                                href={routes.admin.dashboard()}
                                className="ml-1 inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-sm font-medium text-link hover:bg-brand-soft"
                            >
                                <LayoutDashboard className="size-4" aria-hidden="true" />
                                Admin
                            </Link>
                        )}
                    </nav>
                </div>
            </header>

            <FlashMessages />

            <main className="flex-1">{children}</main>

            <footer className="border-t border-hairline bg-surface">
                <div className="mx-auto flex max-w-6xl flex-col items-center justify-between gap-2 px-4 py-6 text-xs text-ink-secondary sm:flex-row">
                    <p>{props.appName} · Try the projects and help us find bugs.</p>
                    {!user && (
                        <Link href={routes.login()} className="hover:text-ink hover:underline">
                            Admin sign in
                        </Link>
                    )}
                </div>
            </footer>
        </div>
    );
}
