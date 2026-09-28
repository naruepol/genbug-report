import ProjectForm from '@/Components/ProjectForm';
import AdminLayout from '@/Layouts/AdminLayout';
import { routes } from '@/lib/routes';
import { Head } from '@inertiajs/react';

export default function CreateProject() {
    return (
        <AdminLayout
            title="New project"
            description="Publish the project to generate its QR code and open it for bug reports."
            breadcrumbs={[{ label: 'Projects', href: routes.admin.projects() }]}
        >
            <Head title="New project" />
            <ProjectForm />
        </AdminLayout>
    );
}
