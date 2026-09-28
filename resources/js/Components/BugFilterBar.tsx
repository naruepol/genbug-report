import { Select, TextInput } from '@/Components/Form';
import { BUG_STATUSES, PRIORITIES, SEVERITIES, VERIFICATION_STATUSES } from '@/lib/enums';
import { buttonClass } from '@/lib/ui';
import type { AdminBugFilters, PublicBugFilters } from '@/types';
import { router } from '@inertiajs/react';
import { Search, X } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';

type Filters = PublicBugFilters & Partial<AdminBugFilters>;

interface BugFilterBarProps {
    url: string;
    filters: Filters;
    /** Admin mode adds project, priority and a created-date range. */
    admin?: boolean;
    projects?: { id: number; name: string; project_code: string }[];
    /** Props to reload when filtering (keeps the rest of the page as is). */
    only?: string[];
}

/**
 * One row of filters above the list it scopes. Selects apply immediately;
 * the search box applies after a short pause in typing.
 */
export default function BugFilterBar({ url, filters, admin = false, projects = [], only }: BugFilterBarProps) {
    const [values, setValues] = useState<Filters>(filters);
    const [search, setSearch] = useState(filters.search ?? '');
    const firstRender = useRef(true);

    function apply(next: Filters) {
        setValues(next);

        const query = Object.fromEntries(
            Object.entries(next).filter(([, value]) => value !== null && value !== undefined && value !== ''),
        ) as Record<string, string | number>;

        router.get(url, query, { preserveState: true, preserveScroll: true, replace: true, only });
    }

    function update<K extends keyof Filters>(key: K, value: Filters[K]) {
        apply({ ...values, search, [key]: value === '' ? null : value });
    }

    // Debounce the search box.
    useEffect(() => {
        if (firstRender.current) {
            firstRender.current = false;
            return;
        }

        const timer = window.setTimeout(() => apply({ ...values, search }), 350);

        return () => window.clearTimeout(timer);
    }, [search]);

    const hasFilters = Boolean(
        search ||
            values.status ||
            values.severity ||
            values.verification ||
            values.project ||
            values.priority ||
            values.from ||
            values.to,
    );

    return (
        <div className="flex flex-col gap-2 lg:flex-row lg:flex-wrap lg:items-end">
            <div className="relative lg:w-64">
                <label htmlFor="bug-search" className="sr-only">
                    Search bugs
                </label>
                <Search className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-ink-muted" aria-hidden="true" />
                <TextInput
                    id="bug-search"
                    type="search"
                    value={search}
                    onChange={(event) => setSearch(event.target.value)}
                    placeholder={admin ? 'Bug ID, title, description, reporter email' : 'Search Bug ID or title'}
                    className="pl-9"
                />
            </div>

            <div className="grid grid-cols-2 gap-2 sm:grid-cols-3 lg:flex lg:flex-wrap">
                {admin && (
                    <FilterSelect
                        label="Project"
                        value={values.project?.toString() ?? ''}
                        onChange={(value) => update('project', value ? Number(value) : null)}
                        options={projects.map((project) => ({ value: String(project.id), label: project.name }))}
                    />
                )}
                <FilterSelect
                    label="Status"
                    value={values.status ?? ''}
                    onChange={(value) => update('status', value as Filters['status'])}
                    options={BUG_STATUSES}
                />
                <FilterSelect
                    label="Severity"
                    value={values.severity ?? ''}
                    onChange={(value) => update('severity', value as Filters['severity'])}
                    options={SEVERITIES}
                />
                {admin && (
                    <FilterSelect
                        label="Priority"
                        value={values.priority ?? ''}
                        onChange={(value) => update('priority', value as Filters['priority'])}
                        options={PRIORITIES}
                    />
                )}
                <FilterSelect
                    label="Verification"
                    value={values.verification ?? ''}
                    onChange={(value) => update('verification', value as Filters['verification'])}
                    options={VERIFICATION_STATUSES}
                />
                {admin && (
                    <>
                        <DateFilter label="From" value={values.from ?? ''} onChange={(value) => update('from', value || null)} />
                        <DateFilter label="To" value={values.to ?? ''} onChange={(value) => update('to', value || null)} />
                    </>
                )}
            </div>

            {hasFilters && (
                <button
                    type="button"
                    onClick={() => {
                        setSearch('');
                        apply({ search: '', status: null, severity: null, verification: null });
                    }}
                    className={buttonClass('ghost', 'md', 'self-start lg:self-auto')}
                >
                    <X className="size-4" aria-hidden="true" />
                    Clear filters
                </button>
            )}
        </div>
    );
}

function FilterSelect({
    label,
    value,
    onChange,
    options,
}: {
    label: string;
    value: string;
    onChange: (value: string) => void;
    options: { value: string; label: string }[];
}) {
    return (
        <label className="block lg:w-40">
            <span className="sr-only">{label}</span>
            <Select value={value} onChange={(event) => onChange(event.target.value)} aria-label={label}>
                <option value="">Any {label.toLowerCase()}</option>
                {options.map((option) => (
                    <option key={option.value} value={option.value}>
                        {option.label}
                    </option>
                ))}
            </Select>
        </label>
    );
}

function DateFilter({ label, value, onChange }: { label: string; value: string; onChange: (value: string) => void }) {
    return (
        <label className="block lg:w-40">
            <span className="mb-1 block text-xs text-ink-secondary lg:sr-only">{label} date</span>
            <TextInput type="date" value={value} onChange={(event) => onChange(event.target.value)} aria-label={`Reported ${label.toLowerCase()}`} />
        </label>
    );
}
