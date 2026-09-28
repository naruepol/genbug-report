import { Inbox, type LucideIcon } from 'lucide-react';
import type { ReactNode } from 'react';

interface EmptyStateProps {
    title: string;
    description?: ReactNode;
    icon?: LucideIcon;
    action?: ReactNode;
}

export default function EmptyState({ title, description, icon: Icon = Inbox, action }: EmptyStateProps) {
    return (
        <div className="flex flex-col items-center px-6 py-12 text-center">
            <Icon className="size-8 text-ink-muted" aria-hidden="true" />
            <p className="mt-3 text-sm font-semibold text-ink">{title}</p>
            {description && <p className="mt-1 max-w-sm text-sm text-ink-secondary">{description}</p>}
            {action && <div className="mt-4">{action}</div>}
        </div>
    );
}
