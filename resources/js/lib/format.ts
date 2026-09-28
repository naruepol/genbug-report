const dateFormatter = new Intl.DateTimeFormat(undefined, { day: 'numeric', month: 'short', year: 'numeric' });
const dateTimeFormatter = new Intl.DateTimeFormat(undefined, {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
});
const numberFormatter = new Intl.NumberFormat();

/** Formats an ISO timestamp or a Y-m-d date in the viewer's locale and time zone. */
export function formatDate(value: string | null | undefined): string {
    if (!value) return '—';

    // A bare Y-m-d is a calendar date; parse it as local midnight so it never shifts a day.
    const date = /^\d{4}-\d{2}-\d{2}$/.test(value) ? new Date(`${value}T00:00:00`) : new Date(value);

    return Number.isNaN(date.getTime()) ? value : dateFormatter.format(date);
}

export function formatDateTime(value: string | null | undefined): string {
    if (!value) return '—';

    const date = new Date(value);

    return Number.isNaN(date.getTime()) ? value : dateTimeFormatter.format(date);
}

export function formatNumber(value: number): string {
    return numberFormatter.format(value);
}

export function formatFileSize(bytes: number | undefined): string {
    if (bytes === undefined) return '';
    if (bytes < 1024) return `${bytes} B`;
    if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(0)} KB`;

    return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
}

export function pluralize(count: number, singular: string, plural = `${singular}s`): string {
    return `${formatNumber(count)} ${count === 1 ? singular : plural}`;
}

export function classNames(...classes: Array<string | false | null | undefined>): string {
    return classes.filter(Boolean).join(' ');
}
