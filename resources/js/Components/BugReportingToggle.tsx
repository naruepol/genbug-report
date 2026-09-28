import Switch from '@/Components/Switch';
import { routes } from '@/lib/routes';
import type { Project } from '@/types';
import { router } from '@inertiajs/react';
import { useState } from 'react';

interface BugReportingToggleProps {
    project: Pick<Project, 'id' | 'name' | 'bug_reporting_enabled'>;
    size?: 'sm' | 'md';
}

/** Admin switch that turns public bug reporting on or off for one project. */
export default function BugReportingToggle({ project, size = 'md' }: BugReportingToggleProps) {
    const [busy, setBusy] = useState(false);

    function toggle(enabled: boolean) {
        router.patch(
            routes.admin.bugReporting(project.id),
            { enabled },
            {
                preserveScroll: true,
                preserveState: true,
                onStart: () => setBusy(true),
                onFinish: () => setBusy(false),
            },
        );
    }

    return (
        <Switch
            checked={project.bug_reporting_enabled}
            onChange={toggle}
            label={`Bug reporting for ${project.name}`}
            disabled={busy}
            size={size}
        />
    );
}

/** One-line explanation of whether a project currently takes new reports. */
export function bugReportingSummary(project: Pick<Project, 'status' | 'bug_reporting_enabled'>): string {
    if (!project.bug_reporting_enabled) {
        return 'Off — the project stays visible, but new bug reports are not accepted.';
    }

    switch (project.status) {
        case 'published':
            return 'On — visitors can report bugs.';
        case 'draft':
            return 'On — reports open once the project is published.';
        case 'closed':
            return 'On, but the project is closed, so new reports are not accepted.';
    }
}
