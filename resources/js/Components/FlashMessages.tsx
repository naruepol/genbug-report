import { usePage } from '@inertiajs/react';
import { CircleAlert, CircleCheck, X } from 'lucide-react';
import { useEffect, useState } from 'react';

/**
 * Shows one-time success/error messages flashed by the server (Inertia::flash).
 * A new response carries a new flash object, which makes the message visible again.
 */
export default function FlashMessages() {
    const { flash } = usePage();
    const [visible, setVisible] = useState(true);

    useEffect(() => {
        setVisible(true);

        const timer = window.setTimeout(() => setVisible(false), 7000);

        return () => window.clearTimeout(timer);
    }, [flash]);

    const messages = [
        flash.success && { type: 'success' as const, text: flash.success },
        flash.error && { type: 'error' as const, text: flash.error },
    ].filter((message): message is { type: 'success' | 'error'; text: string } => Boolean(message));

    if (!visible || messages.length === 0) {
        return null;
    }

    return (
        <div className="pointer-events-none fixed inset-x-0 top-3 z-40 flex flex-col items-center gap-2 px-4" aria-live="polite">
            {messages.map((message) => (
                <div
                    key={message.type}
                    role={message.type === 'error' ? 'alert' : 'status'}
                    className="pointer-events-auto flex w-full max-w-md items-start gap-3 rounded-xl bg-surface px-4 py-3 text-sm text-ink shadow-lg ring-1 ring-hairline"
                >
                    {message.type === 'success' ? (
                        <CircleCheck className="mt-0.5 size-5 shrink-0 text-status-good" aria-hidden="true" />
                    ) : (
                        <CircleAlert className="mt-0.5 size-5 shrink-0 text-status-critical" aria-hidden="true" />
                    )}
                    <p className="flex-1">{message.text}</p>
                    <button
                        type="button"
                        onClick={() => setVisible(false)}
                        className="-m-1 inline-flex size-7 items-center justify-center rounded-md text-ink-muted hover:bg-chip hover:text-ink"
                        aria-label="Dismiss"
                    >
                        <X className="size-4" />
                    </button>
                </div>
            ))}
        </div>
    );
}
