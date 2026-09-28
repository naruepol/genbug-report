import ActivityList from '@/Components/ActivityList';
import { CategoryLabel, PriorityBadge, ScoreBadge, SeverityBadge, StatusBadge, VerificationBadge } from '@/Components/BugBadge';
import { describedBy, Field, Select, TextArea } from '@/Components/Form';
import AdminLayout from '@/Layouts/AdminLayout';
import { BUG_STATUSES, PRIORITIES, scoreBand, SEVERITIES } from '@/lib/enums';
import { formatDateTime, formatFileSize } from '@/lib/format';
import { routes } from '@/lib/routes';
import { buttonClass, cardClass } from '@/lib/ui';
import type { AdminBug, AuditEntry, BugPriority, BugSeverity, BugStatus, VerificationStatus } from '@/types';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { CircleCheck, CircleX, Copy, ExternalLink, LoaderCircle, Lock, Trash } from 'lucide-react';
import { useState, type FormEvent, type ReactNode } from 'react';

interface BugShowProps {
    bug: AdminBug;
    activity: AuditEntry[];
}

export default function BugShow({ bug, activity }: BugShowProps) {
    const [busy, setBusy] = useState<VerificationStatus | 'delete' | null>(null);

    // Quick verification actions send only the verification status.
    function setVerification(verification: VerificationStatus) {
        router.patch(
            routes.admin.bug(bug.id),
            { verification_status: verification },
            { preserveScroll: true, onStart: () => setBusy(verification), onFinish: () => setBusy(null) },
        );
    }

    function destroy() {
        if (window.confirm(`Delete ${bug.bug_code} as spam? Its screenshot will be deleted too. This cannot be undone.`)) {
            router.delete(routes.admin.bug(bug.id), { onStart: () => setBusy('delete'), onFinish: () => setBusy(null) });
        }
    }

    return (
        <AdminLayout
            title={bug.title}
            description={
                <span className="flex flex-wrap items-center gap-2">
                    <span className="font-semibold text-ink tabular">{bug.bug_code}</span>
                    {bug.project && (
                        <>
                            <span aria-hidden="true">·</span>
                            <Link href={routes.admin.project(bug.project.id ?? 0)} className="hover:text-ink hover:underline">
                                {bug.project.name}
                            </Link>
                        </>
                    )}
                    <span aria-hidden="true">·</span>
                    <span>Reported {formatDateTime(bug.created_at)}</span>
                </span>
            }
            breadcrumbs={[{ label: 'Bugs', href: routes.admin.bugs() }]}
            actions={
                <a href={routes.bug(bug.bug_code)} target="_blank" rel="noreferrer" className={buttonClass('secondary')}>
                    <ExternalLink className="size-4" aria-hidden="true" />
                    Public view
                </a>
            }
        >
            <Head title={`${bug.bug_code} · ${bug.title}`} />

            <div className="grid gap-6 xl:grid-cols-[1fr_24rem]">
                <div className="min-w-0 space-y-6">
                    <dl className={`${cardClass} grid grid-cols-2 gap-4 p-4 sm:grid-cols-3 lg:grid-cols-6`}>
                        <Meta label="Verification">
                            <VerificationBadge status={bug.verification_status} />
                        </Meta>
                        <Meta label="Status">
                            <StatusBadge status={bug.status} />
                        </Meta>
                        <Meta label="Severity">
                            <SeverityBadge severity={bug.severity} />
                        </Meta>
                        <Meta label="Priority">
                            <PriorityBadge priority={bug.priority} />
                        </Meta>
                        <Meta label="Score">
                            <ScoreBadge score={bug.score} />
                        </Meta>
                        <Meta label="Category">
                            {bug.category ? <CategoryLabel category={bug.category} /> : <span className="text-sm text-ink-muted">—</span>}
                        </Meta>
                    </dl>

                    <TextBlock title="Description" text={bug.description} />
                    <TextBlock title="Steps to reproduce" text={bug.steps_to_reproduce} />
                    <div className="grid gap-6 md:grid-cols-2">
                        <TextBlock title="Expected result" text={bug.expected_result} />
                        <TextBlock title="Actual result" text={bug.actual_result} />
                    </div>

                    <section className={`${cardClass} p-4 sm:p-5`}>
                        <h2 className="text-sm font-semibold text-ink">Environment</h2>
                        <dl className="mt-3 grid gap-4 text-sm sm:grid-cols-2 lg:grid-cols-4">
                            <Info label="Page / Screen" value={bug.page_screen} />
                            <Info label="Browser" value={bug.browser} />
                            <Info label="Operating system" value={bug.operating_system} />
                            <Info label="Device" value={bug.device} />
                        </dl>
                    </section>

                    <section className={`${cardClass} p-4 sm:p-5`}>
                        <h2 className="text-sm font-semibold text-ink">Screenshot</h2>
                        {bug.screenshots && bug.screenshots.length > 0 ? (
                            <div className="mt-3 grid gap-4 sm:grid-cols-2">
                                {bug.screenshots.map((screenshot) => (
                                    <figure key={screenshot.id}>
                                        <a href={screenshot.url} target="_blank" rel="noopener noreferrer" className="block overflow-hidden rounded-lg ring-1 ring-hairline hover:ring-brand">
                                            <img src={screenshot.url} alt={`Screenshot for ${bug.bug_code}`} className="max-h-96 w-full bg-chip object-contain" />
                                        </a>
                                        <figcaption className="mt-1.5 truncate text-xs text-ink-secondary">
                                            {screenshot.file_name} · {formatFileSize(screenshot.file_size)}
                                        </figcaption>
                                    </figure>
                                ))}
                            </div>
                        ) : (
                            <p className="mt-2 text-sm text-ink-muted">No screenshot attached.</p>
                        )}
                    </section>

                    <section className={`${cardClass} overflow-hidden`}>
                        <h2 className="border-b border-hairline px-4 py-3 text-sm font-semibold text-ink">Activity</h2>
                        <ActivityList entries={activity} empty="No admin actions on this bug yet." />
                    </section>
                </div>

                <aside className="space-y-6">
                    <section className={`${cardClass} p-4 sm:p-5`}>
                        <h2 className="text-sm font-semibold text-ink">Verification</h2>
                        <p className="mt-1 text-sm text-ink-secondary">
                            Rejected and duplicate reports get the matching status automatically.
                        </p>
                        <div className="mt-3 grid grid-cols-3 gap-2">
                            <VerificationButton
                                label="Verify"
                                icon={<CircleCheck className="size-4 text-status-good" aria-hidden="true" />}
                                active={bug.verification_status === 'verified'}
                                busy={busy === 'verified'}
                                disabled={busy !== null}
                                onClick={() => setVerification('verified')}
                            />
                            <VerificationButton
                                label="Reject"
                                icon={<CircleX className="size-4 text-status-critical" aria-hidden="true" />}
                                active={bug.verification_status === 'rejected'}
                                busy={busy === 'rejected'}
                                disabled={busy !== null}
                                onClick={() => setVerification('rejected')}
                            />
                            <VerificationButton
                                label="Duplicate"
                                icon={<Copy className="size-4 text-ink-muted" aria-hidden="true" />}
                                active={bug.verification_status === 'duplicate'}
                                busy={busy === 'duplicate'}
                                disabled={busy !== null}
                                onClick={() => setVerification('duplicate')}
                            />
                        </div>
                        {bug.verification_status !== 'pending' && (
                            <button
                                type="button"
                                onClick={() => setVerification('pending')}
                                disabled={busy !== null}
                                className={buttonClass('ghost', 'sm', 'mt-2 w-full')}
                            >
                                Move back to pending
                            </button>
                        )}
                    </section>

                    <ReviewForm key={bug.updated_at} bug={bug} />

                    <section className={`${cardClass} p-4 sm:p-5`}>
                        <h2 className="flex items-center gap-1.5 text-sm font-semibold text-ink">
                            <Lock className="size-4 text-ink-muted" aria-hidden="true" />
                            Reporter information
                        </h2>
                        <p className="mt-1 text-xs text-ink-secondary">Admin only. The public always sees “Anonymous User”.</p>
                        <dl className="mt-3 space-y-3 text-sm">
                            <Info label="Name" value={bug.reporter_name} />
                            <Info
                                label="Email"
                                value={bug.reporter_email}
                                render={(email) => (
                                    <a href={`mailto:${email}`} className="break-all text-link hover:underline">
                                        {email}
                                    </a>
                                )}
                            />
                            <Info label="IP address" value={bug.reporter_ip} />
                        </dl>
                    </section>

                    <section className={`${cardClass} p-4 sm:p-5`}>
                        <h2 className="text-sm font-semibold text-ink">Spam</h2>
                        <p className="mt-1 text-sm text-ink-secondary">Delete reports that are spam or abusive.</p>
                        <button type="button" onClick={destroy} disabled={busy !== null} className={buttonClass('danger', 'md', 'mt-3')}>
                            <Trash className="size-4" aria-hidden="true" />
                            Delete as spam
                        </button>
                    </section>
                </aside>
            </div>
        </AdminLayout>
    );
}

function ReviewForm({ bug }: { bug: AdminBug }) {
    const form = useForm({
        severity: (bug.severity ?? '') as BugSeverity | '',
        priority: (bug.priority ?? '') as BugPriority | '',
        score: bug.score?.toString() ?? '',
        status: bug.status as BugStatus,
        admin_note: bug.admin_note ?? '',
    });
    const { data, setData, errors, processing, recentlySuccessful } = form;

    function submit(event: FormEvent) {
        event.preventDefault();
        form.transform((values) => ({
            ...values,
            severity: values.severity || null,
            priority: values.priority || null,
            score: values.score === '' ? null : Number(values.score),
        }));
        form.patch(routes.admin.bug(bug.id), { preserveScroll: true });
    }

    return (
        <form onSubmit={submit} className={`${cardClass} space-y-4 p-4 sm:p-5`}>
            <h2 className="text-sm font-semibold text-ink">Assessment</h2>

            <div className="grid grid-cols-2 gap-3">
                <Field id="severity" label="Severity" error={errors.severity}>
                    <Select
                        id="severity"
                        value={data.severity}
                        onChange={(event) => setData('severity', event.target.value as BugSeverity | '')}
                        {...describedBy('severity', { error: errors.severity })}
                    >
                        <option value="">Not set</option>
                        {SEVERITIES.map((option) => (
                            <option key={option.value} value={option.value}>
                                {option.label}
                            </option>
                        ))}
                    </Select>
                </Field>
                <Field id="priority" label="Priority" error={errors.priority}>
                    <Select
                        id="priority"
                        value={data.priority}
                        onChange={(event) => setData('priority', event.target.value as BugPriority | '')}
                        {...describedBy('priority', { error: errors.priority })}
                    >
                        <option value="">Not set</option>
                        {PRIORITIES.map((option) => (
                            <option key={option.value} value={option.value}>
                                {option.label}
                            </option>
                        ))}
                    </Select>
                </Field>
            </div>

            <Field id="score" label="Score (1–10)" error={errors.score} hint="1–3 Low · 4–6 Medium · 7–8 High · 9–10 Critical">
                <Select
                    id="score"
                    value={data.score}
                    onChange={(event) => setData('score', event.target.value)}
                    {...describedBy('score', { error: errors.score, hint: true })}
                >
                    <option value="">Not scored</option>
                    {Array.from({ length: 10 }, (_, index) => index + 1).map((score) => (
                        <option key={score} value={score}>
                            {score} — {scoreBand(score).label}
                        </option>
                    ))}
                </Select>
            </Field>

            <Field id="status" label="Status" error={errors.status} hint="Open → In Progress → Fixed → Closed">
                <Select
                    id="status"
                    value={data.status}
                    onChange={(event) => setData('status', event.target.value as BugStatus)}
                    {...describedBy('status', { error: errors.status, hint: true })}
                >
                    {BUG_STATUSES.map((option) => (
                        <option key={option.value} value={option.value}>
                            {option.label}
                        </option>
                    ))}
                </Select>
            </Field>

            <Field id="admin_note" label="Admin note" error={errors.admin_note} hint="Only visible to admins">
                <TextArea
                    id="admin_note"
                    value={data.admin_note}
                    onChange={(event) => setData('admin_note', event.target.value)}
                    rows={4}
                    {...describedBy('admin_note', { error: errors.admin_note, hint: true })}
                />
            </Field>

            <div className="flex items-center gap-3">
                <button type="submit" disabled={processing} className={buttonClass('primary')}>
                    {processing && <LoaderCircle className="size-4 animate-spin" aria-hidden="true" />}
                    Save assessment
                </button>
                {recentlySuccessful && <span className="text-sm text-success-text">Saved</span>}
            </div>
        </form>
    );
}

function VerificationButton({
    label,
    icon,
    active,
    busy,
    disabled,
    onClick,
}: {
    label: string;
    icon: ReactNode;
    active: boolean;
    busy: boolean;
    disabled: boolean;
    onClick: () => void;
}) {
    return (
        <button
            type="button"
            onClick={onClick}
            disabled={disabled || active}
            aria-pressed={active}
            className={
                active
                    ? 'inline-flex flex-col items-center gap-1 rounded-lg bg-chip px-2 py-2.5 text-xs font-semibold text-ink ring-2 ring-ink/80'
                    : 'inline-flex flex-col items-center gap-1 rounded-lg bg-surface px-2 py-2.5 text-xs font-semibold text-ink ring-1 ring-hairline hover:bg-page disabled:opacity-60'
            }
        >
            {busy ? <LoaderCircle className="size-4 animate-spin" aria-hidden="true" /> : icon}
            {label}
        </button>
    );
}

function Meta({ label, children }: { label: string; children: ReactNode }) {
    return (
        <div>
            <dt className="text-xs text-ink-secondary">{label}</dt>
            <dd className="mt-1">{children}</dd>
        </div>
    );
}

function Info({ label, value, render }: { label: string; value: string | null; render?: (value: string) => ReactNode }) {
    return (
        <div>
            <dt className="text-xs text-ink-secondary">{label}</dt>
            <dd className="mt-0.5 break-words text-ink">
                {value ? (render ? render(value) : value) : <span className="text-ink-muted">Not provided</span>}
            </dd>
        </div>
    );
}

function TextBlock({ title, text }: { title: string; text: string | null }) {
    return (
        <section className={`${cardClass} p-4 sm:p-5`}>
            <h2 className="text-sm font-semibold text-ink">{title}</h2>
            {text ? (
                <p className="mt-2 text-sm break-words whitespace-pre-line text-ink-secondary">{text}</p>
            ) : (
                <p className="mt-2 text-sm text-ink-muted">Not provided</p>
            )}
        </section>
    );
}
