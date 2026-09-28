import BugFilterBar from '@/Components/BugFilterBar';
import BugTable from '@/Components/BugTable';
import EmptyState from '@/Components/EmptyState';
import Pagination from '@/Components/Pagination';
import AdminLayout from '@/Layouts/AdminLayout';
import { formatNumber } from '@/lib/format';
import { routes } from '@/lib/routes';
import { cardClass } from '@/lib/ui';
import type { AdminBug, AdminBugFilters, Paginated } from '@/types';
import { Head } from '@inertiajs/react';
import { Bug } from 'lucide-react';

interface BugsIndexProps {
    bugs: Paginated<AdminBug>;
    filters: AdminBugFilters;
    projects: { id: number; name: string; project_code: string }[];
}

export default function BugsIndex({ bugs, filters, projects }: BugsIndexProps) {
    return (
        <AdminLayout
            title="Bugs"
            description={`${formatNumber(bugs.meta.total)} ${bugs.meta.total === 1 ? 'report matches' : 'reports match'} the current filters.`}
        >
            <Head title="Bugs" />

            <BugFilterBar url={routes.admin.bugs()} filters={filters} admin projects={projects} />

            <div className={`${cardClass} mt-4 overflow-hidden`}>
                <BugTable
                    bugs={bugs.data}
                    href={(bug) => routes.admin.bug(bug.id)}
                    showProject
                    showPriority
                    showReported
                    caption="Bug reports"
                    empty={<EmptyState icon={Bug} title="No bug reports found" description="Try changing or clearing the filters." />}
                />
            </div>

            <div className="mt-4">
                <Pagination meta={bugs.meta} />
            </div>
        </AdminLayout>
    );
}
