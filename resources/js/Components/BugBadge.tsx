import {
    BUG_STATUSES,
    CATEGORIES,
    findOption,
    PRIORITIES,
    PROJECT_STATUSES,
    scoreBand,
    SEVERITIES,
    TONE_DOT_CLASS,
    VERIFICATION_STATUSES,
    type Tone,
} from '@/lib/enums';
import { classNames } from '@/lib/format';
import type { BugCategory, BugPriority, BugSeverity, BugStatus, ProjectStatus, VerificationStatus } from '@/types';

interface BadgeProps {
    label: string;
    tone: Tone;
    title?: string;
    className?: string;
}

/**
 * A neutral chip whose coloured dot carries the state; the text label always
 * stays in the ink colour, so meaning never depends on colour alone.
 */
export function Badge({ label, tone, title, className }: BadgeProps) {
    return (
        <span
            title={title}
            className={classNames(
                'inline-flex items-center gap-1.5 rounded-full bg-chip px-2 py-0.5 text-xs font-medium whitespace-nowrap text-ink ring-1 ring-black/5 ring-inset',
                className,
            )}
        >
            <span aria-hidden="true" className={classNames('size-2 shrink-0 rounded-full', TONE_DOT_CLASS[tone])} />
            {label}
        </span>
    );
}

export function NotSet({ label = 'Not assessed' }: { label?: string }) {
    return (
        <span className="text-sm text-ink-muted" title={label}>
            <span aria-hidden="true">—</span>
            <span className="sr-only">{label}</span>
        </span>
    );
}

export function StatusBadge({ status }: { status: BugStatus }) {
    const option = findOption(BUG_STATUSES, status);

    return option ? <Badge label={option.label} tone={option.tone} /> : null;
}

export function VerificationBadge({ status }: { status: VerificationStatus }) {
    const option = findOption(VERIFICATION_STATUSES, status);

    return option ? <Badge label={option.label} tone={option.tone} /> : null;
}

export function SeverityBadge({ severity }: { severity: BugSeverity | null }) {
    const option = findOption(SEVERITIES, severity);

    return option ? <Badge label={option.label} tone={option.tone} /> : <NotSet />;
}

export function PriorityBadge({ priority }: { priority: BugPriority | null }) {
    const option = findOption(PRIORITIES, priority);

    return option ? <Badge label={option.label} tone={option.tone} /> : <NotSet />;
}

export function ScoreBadge({ score }: { score: number | null }) {
    if (score === null) {
        return <NotSet />;
    }

    const band = scoreBand(score);

    return <Badge label={`${score} · ${band.label}`} tone={band.tone} title={`Score ${score} of 10 (${band.label})`} />;
}

export function ProjectStatusBadge({ status }: { status: ProjectStatus }) {
    const option = findOption(PROJECT_STATUSES, status);

    return option ? <Badge label={option.label} tone={option.tone} /> : null;
}

export function CategoryLabel({ category }: { category: BugCategory | null }) {
    const option = findOption(CATEGORIES, category);

    return option ? (
        <span className="inline-flex items-center rounded-md bg-chip px-1.5 py-0.5 text-xs font-medium text-ink-secondary">
            {option.label}
        </span>
    ) : null;
}

type BugBadgeProps =
    | { kind: 'status'; value: BugStatus }
    | { kind: 'verification'; value: VerificationStatus }
    | { kind: 'severity'; value: BugSeverity | null }
    | { kind: 'priority'; value: BugPriority | null }
    | { kind: 'score'; value: number | null };

export default function BugBadge(props: BugBadgeProps) {
    switch (props.kind) {
        case 'status':
            return <StatusBadge status={props.value} />;
        case 'verification':
            return <VerificationBadge status={props.value} />;
        case 'severity':
            return <SeverityBadge severity={props.value} />;
        case 'priority':
            return <PriorityBadge priority={props.value} />;
        case 'score':
            return <ScoreBadge score={props.value} />;
    }
}
