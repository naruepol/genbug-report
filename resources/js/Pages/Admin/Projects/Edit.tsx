import ProjectForm from '@/Components/ProjectForm';
import AdminLayout from '@/Layouts/AdminLayout';
import { routes } from '@/lib/routes';
import type { Project } from '@/types';
import { Head } from '@inertiajs/react';

export default function EditProject({ project }: { project: Project }) {
    return (
        <AdminLayout
            title={`Edit ${project.name}`}
            breadcrumbs={[
                { label: 'Projects', href: routes.admin.projects() },
                { label: project.project_code, href: routes.admin.project(project.id) },
            ]}
        >
            <Head title={`Edit ${project.name}`} />
            <ProjectForm project={project} />
        </AdminLayout>
    );
}
