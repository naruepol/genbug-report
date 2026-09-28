import FlashMessages from '@/Components/FlashMessages';
import { routes } from '@/lib/routes';
import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, Bug, TriangleAlert } from 'lucide-react';

interface LoginProps {
    googleConfigured: boolean;
}

export default function Login({ googleConfigured }: LoginProps) {
    return (
        <div className="flex min-h-screen flex-col items-center justify-center px-4 py-12">
            <Head title="Admin sign in" />
            <FlashMessages />

            <div className="w-full max-w-sm">
                <div className="flex flex-col items-center text-center">
                    <span className="inline-flex size-12 items-center justify-center rounded-xl bg-brand text-white">
                        <Bug className="size-6" aria-hidden="true" />
                    </span>
                    <h1 className="mt-4 text-2xl font-semibold text-ink">Admin sign in</h1>
                    <p className="mt-1 text-sm text-ink-secondary">
                        For project owners who manage projects and review bug reports. Reporting a bug does not need an account.
                    </p>
                </div>

                <div className="mt-8 rounded-xl bg-surface p-6 ring-1 ring-hairline">
                    {/* A full page navigation: the OAuth flow leaves the app, so this is not an Inertia link. */}
                    <a
                        href={routes.googleLogin()}
                        className="flex w-full items-center justify-center gap-3 rounded-lg bg-surface px-4 py-2.5 text-sm font-semibold text-ink shadow-sm ring-1 ring-hairline ring-inset hover:bg-page"
                    >
                        <GoogleLogo />
                        Sign in with Google
                    </a>

                    <p className="mt-4 text-center text-xs text-ink-secondary">
                        Only Google accounts listed by the site owner can sign in.
                    </p>

                    {!googleConfigured && (
                        <div role="note" className="mt-5 flex gap-2 rounded-lg bg-page p-3 text-xs text-ink-secondary ring-1 ring-hairline">
                            <TriangleAlert className="size-4 shrink-0 text-status-warning" aria-hidden="true" />
                            <p>
                                Google sign-in is not configured yet. Set <code className="font-mono text-ink">GOOGLE_CLIENT_ID</code>{' '}
                                and <code className="font-mono text-ink">GOOGLE_CLIENT_SECRET</code> in <code>.env</code>. For local
                                development you can run{' '}
                                <code className="font-mono text-ink">php artisan admin:login-link</code> instead.
                            </p>
                        </div>
                    )}
                </div>

                <Link href={routes.home()} className="mt-6 flex items-center justify-center gap-1.5 text-sm text-ink-secondary hover:text-ink">
                    <ArrowLeft className="size-4" aria-hidden="true" />
                    Back to the projects
                </Link>
            </div>
        </div>
    );
}

function GoogleLogo() {
    return (
        <svg viewBox="0 0 24 24" className="size-5" aria-hidden="true">
            <path fill="#4285F4" d="M23.52 12.27c0-.85-.08-1.66-.22-2.45H12v4.63h6.47a5.53 5.53 0 0 1-2.4 3.63v3h3.88c2.27-2.09 3.57-5.17 3.57-8.81z" />
            <path fill="#34A853" d="M12 24c3.24 0 5.96-1.07 7.95-2.91l-3.88-3.01c-1.08.72-2.45 1.15-4.07 1.15-3.13 0-5.78-2.11-6.73-4.96H1.26v3.11A12 12 0 0 0 12 24z" />
            <path fill="#FBBC05" d="M5.27 14.27A7.2 7.2 0 0 1 4.89 12c0-.79.14-1.55.38-2.27V6.62H1.26A12 12 0 0 0 0 12c0 1.94.46 3.77 1.26 5.38l4.01-3.11z" />
            <path fill="#EA4335" d="M12 4.77c1.76 0 3.34.61 4.59 1.8l3.44-3.44C17.95 1.19 15.24 0 12 0A12 12 0 0 0 1.26 6.62l4.01 3.11C6.22 6.88 8.87 4.77 12 4.77z" />
        </svg>
    );
}
