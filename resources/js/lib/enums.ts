import type { BugCategory, BugPriority, BugSeverity, BugStatus, ProjectStatus, VerificationStatus } from '@/types';

/**
 * Labels mirror the PHP enums in app/Enums; keep both in sync when adding a case.
 *
 * A tone only picks the colour of the small dot shown next to a label.
 * Status tones (good → critical) are reserved for states that mean good or bad.
 */
export type Tone = 'good' | 'warning' | 'serious' | 'critical' | 'info' | 'neutral' | 'muted';

export interface Option<T extends string = string> {
    value: T;
    label: string;
    tone: Tone;
}

export const PROJECT_STATUSES: Option<ProjectStatus>[] = [
    { value: 'draft', label: 'Draft', tone: 'muted' },
    { value: 'published', label: 'Published', tone: 'good' },
    { value: 'closed', label: 'Closed', tone: 'neutral' },
];

export const BUG_STATUSES: Option<BugStatus>[] = [
    { value: 'open', label: 'Open', tone: 'info' },
    { value: 'in_progress', label: 'In Progress', tone: 'warning' },
    { value: 'fixed', label: 'Fixed', tone: 'good' },
    { value: 'closed', label: 'Closed', tone: 'neutral' },
    { value: 'rejected', label: 'Rejected', tone: 'critical' },
    { value: 'duplicate', label: 'Duplicate', tone: 'muted' },
];

export const VERIFICATION_STATUSES: Option<VerificationStatus>[] = [
    { value: 'pending', label: 'Pending', tone: 'warning' },
    { value: 'verified', label: 'Verified', tone: 'good' },
    { value: 'rejected', label: 'Rejected', tone: 'critical' },
    { value: 'duplicate', label: 'Duplicate', tone: 'muted' },
];

export const SEVERITIES: Option<BugSeverity>[] = [
    { value: 'critical', label: 'Critical', tone: 'critical' },
    { value: 'high', label: 'High', tone: 'serious' },
    { value: 'medium', label: 'Medium', tone: 'warning' },
    { value: 'low', label: 'Low', tone: 'info' },
];

export const PRIORITIES: Option<BugPriority>[] = [
    { value: 'high', label: 'High', tone: 'serious' },
    { value: 'medium', label: 'Medium', tone: 'warning' },
    { value: 'low', label: 'Low', tone: 'info' },
];

export const CATEGORIES: Option<BugCategory>[] = [
    { value: 'functional', label: 'Functional', tone: 'neutral' },
    { value: 'ui_ux', label: 'UI/UX', tone: 'neutral' },
    { value: 'performance', label: 'Performance', tone: 'neutral' },
    { value: 'compatibility', label: 'Compatibility', tone: 'neutral' },
    { value: 'content', label: 'Content', tone: 'neutral' },
    { value: 'other', label: 'Other', tone: 'neutral' },
    { value: 'not_sure', label: 'Not Sure', tone: 'neutral' },
];

export function findOption<T extends string>(options: Option<T>[], value: T | null | undefined): Option<T> | undefined {
    return options.find((option) => option.value === value);
}

export function labelOf<T extends string>(options: Option<T>[], value: T | null | undefined, fallback = '—'): string {
    return findOption(options, value)?.label ?? fallback;
}

/** Score bands from the spec: 1-3 Low, 4-6 Medium, 7-8 High, 9-10 Critical. */
export function scoreBand(score: number): Option<BugSeverity> {
    if (score >= 9) return SEVERITIES[0];
    if (score >= 7) return SEVERITIES[1];
    if (score >= 4) return SEVERITIES[2];
    return SEVERITIES[3];
}

export const TONE_DOT_CLASS: Record<Tone, string> = {
    good: 'bg-status-good',
    warning: 'bg-status-warning',
    serious: 'bg-status-serious',
    critical: 'bg-status-critical',
    info: 'bg-series-1',
    neutral: 'bg-ink-secondary',
    muted: 'bg-ink-muted',
};
