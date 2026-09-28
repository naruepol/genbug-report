import { TONE_DOT_CLASS, type Tone } from '@/lib/enums';
import { classNames, formatNumber } from '@/lib/format';
import { Link } from '@inertiajs/react';

interface StatCardProps {
    label: string;
    value: number;
    /** Optional status dot shown next to the label (always paired with the label text). */
    tone?: Tone;
    hint?: string;
    href?: string;
    emphasis?: boolean;
}

/** A stat tile: sentence-case label and a proportional-figure value. */
export default function StatCard({ label, value, tone, hint, href, emphasis = false }: StatCardProps) {
    const body = (
        <dl>
            <dt className="flex items-center gap-1.5 text-sm text-ink-secondary">
                {tone && <span aria-hidden="true" className={classNames('size-2 rounded-full', TONE_DOT_CLASS[tone])} />}
                {label}
            </dt>
            <dd className={classNames('mt-1 font-semibold text-ink', emphasis ? 'text-4xl' : 'text-2xl')}>{formatNumber(value)}</dd>
            {hint && <dd className="mt-0.5 text-xs text-ink-muted">{hint}</dd>}
        </dl>
    );

    // h-full keeps tiles in the same grid row equally tall.
    const className = 'block h-full rounded-xl bg-surface px-4 py-3.5 ring-1 ring-hairline';

    if (href) {
        return (
            <Link href={href} className={classNames(className, 'transition hover:shadow-sm hover:ring-baseline')}>
                {body}
            </Link>
        );
    }

    return <div className={className}>{body}</div>;
}
