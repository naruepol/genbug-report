import { routes } from '@/lib/routes';
import { buttonClass } from '@/lib/ui';
import { Head, Link } from '@inertiajs/react';

interface ErrorProps {
    status: number;
    message?: string;
}

const content: Record<number, { title: string; description: string }> = {
    403: { title: 'Access denied', description: 'You do not have permission to open this page.' },
    404: { title: 'Page not found', description: 'The page or project you are looking for does not exist or is not public.' },
    419: { title: 'Page expired', description: 'Your session expired. Refresh the page and try again.' },
    429: { title: 'Too many requests', description: 'Please wait a moment and try again.' },
    500: { title: 'Something went wrong', description: 'An unexpected error occurred on our side. Please try again later.' },
    503: { title: 'Down for maintenance', description: 'The site is being updated. Please check back soon.' },
};

export default function Error({ status, message }: ErrorProps) {
    const { title, description } = content[status] ?? content[500];

    return (
        <div className="flex min-h-screen items-center justify-center px-4 py-12">
            <Head title={title} />
            <div className="max-w-md text-center">
                <p className="text-sm font-semibold text-link tabular">{status}</p>
                <h1 className="mt-2 text-3xl font-bold tracking-tight text-ink">{title}</h1>
                <p className="mt-3 text-base text-ink-secondary">{message || description}</p>
                <div className="mt-8 flex flex-wrap justify-center gap-3">
                    <Link href={routes.home()} className={buttonClass('primary')}>
                        Go to the home page
                    </Link>
                    {status === 419 && (
                        <button type="button" onClick={() => window.location.reload()} className={buttonClass('secondary')}>
                            Refresh
                        </button>
                    )}
                </div>
            </div>
        </div>
    );
}
