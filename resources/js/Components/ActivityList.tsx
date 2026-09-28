import { BUG_STATUSES, labelOf, PRIORITIES, SEVERITIES, VERIFICATION_STATUSES, type Option } from '@/lib/enums';
import { formatDateTime } from '@/lib/format';
import type { AuditEntry } from '@/types';

const FIELD_LABELS: Record<string, { label: string; options?: Option[] }> = {
    verification_status: { label: 'verification', options: VERIFICATION_STATUSES },
    status: { label: 'status', options: BUG_STATUSES },
    severity: { label: 'severity', options: SEVERITIES },
    priority: { label: 'priority', options: PRIORITIES },
    score: { label: 'score' },
};

function valueLabel(field: string, value: unknown): string {
    if (value === null || value === undefined || value === '') return 'not set';

    const options = FIELD_LABELS[field]?.options;

    return options ? labelOf(options, String(value), String(value)) : String(value);
}

/** Turns an audit log entry into a readable sentence. */
export function describeActivity(entry: AuditEntry): string {
    const meta = entry.metadata ?? {};
    const project = String(meta.project_code ?? '');
    const bug = String(meta.bug_code ?? '');

    switch (entry.action) {
        case 'auth.login':
            return 'Signed in with Google';
        case 'auth.login_link':
            return 'Signed in with a local login link';
        case 'project.created':
            return `Created project ${project}`;
        case 'project.updated': {
            const fields = Array.isArray(meta.fields) ? meta.fields.map(String).join(', ') : '';
            return fields ? `Updated project ${project} (${fields.replaceAll('_', ' ')})` : `Updated project ${project}`;
        }
        case 'project.published':
            return `Published project ${project}`;
        case 'project.closed':
            return `Closed project ${project}`;
        case 'project.bug_reporting_enabled':
            return `Turned on bug reporting for ${project}`;
        case 'project.bug_reporting_disabled':
            return `Turned off bug reporting for ${project}`;
        case 'project.deleted':
            return `Deleted project ${project}`;
        case 'project.qr_generated':
            return `Generated the QR code for ${project}`;
        case 'bug.updated': {
            const changes = (meta.changes ?? {}) as Record<string, unknown>;
            const parts = Object.entries(changes).map(([field, change]) => {
                if (field === 'admin_note') return 'admin note updated';

                const [from, to] = Array.isArray(change) ? change : [null, change];

                return `${FIELD_LABELS[field]?.label ?? field} ${valueLabel(field, from)} → ${valueLabel(field, to)}`;
            });

            return `Updated ${bug}${parts.length ? `: ${parts.join(', ')}` : ''}`;
        }
        case 'bug.deleted':
            return `Deleted ${bug} as spam`;
        default:
            return entry.action;
    }
}

export default function ActivityList({ entries, empty = 'No activity yet.' }: { entries: AuditEntry[]; empty?: string }) {
    if (entries.length === 0) {
        return <p className="px-4 py-6 text-center text-sm text-ink-muted">{empty}</p>;
    }

    return (
        <ol className="divide-y divide-hairline">
            {entries.map((entry) => (
                <li key={entry.id} className="px-4 py-3 text-sm">
                    <p className="text-ink">{describeActivity(entry)}</p>
                    <p className="mt-0.5 text-xs text-ink-muted">
                        {entry.admin ?? 'Unknown admin'} · {formatDateTime(entry.created_at)}
                    </p>
                </li>
            ))}
        </ol>
    );
}
