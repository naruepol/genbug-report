import { describedBy, Field, Select, TextArea, TextInput } from '@/Components/Form';
import Switch from '@/Components/Switch';
import { PROJECT_STATUSES } from '@/lib/enums';
import { routes } from '@/lib/routes';
import { buttonClass, cardClass } from '@/lib/ui';
import type { Project, ProjectStatus } from '@/types';
import { Link, useForm } from '@inertiajs/react';
import { ImagePlus, LoaderCircle } from 'lucide-react';
import { useEffect, useMemo, type FormEvent } from 'react';

interface ProjectFormProps {
    /** Existing project when editing; omitted when creating. */
    project?: Project;
}

/** Create / edit form for a project. Validation errors come from the Form Requests. */
export default function ProjectForm({ project }: ProjectFormProps) {
    const form = useForm({
        name: project?.name ?? '',
        project_code: project?.project_code ?? '',
        description: project?.description ?? '',
        demo_url: project?.demo_url ?? '',
        repository_url: project?.repository_url ?? '',
        technology_stack: project?.technology_stack ?? '',
        presentation_date: project?.presentation_date ?? '',
        status: (project?.status ?? 'draft') as ProjectStatus,
        bug_reporting_enabled: project?.bug_reporting_enabled ?? true,
        image: null as File | null,
        remove_image: false,
    });
    const { data, setData, errors, processing } = form;

    const previewUrl = useMemo(() => (data.image ? URL.createObjectURL(data.image) : null), [data.image]);

    useEffect(() => () => {
        if (previewUrl) URL.revokeObjectURL(previewUrl);
    }, [previewUrl]);

    const currentImage = previewUrl ?? (data.remove_image ? null : project?.image_url ?? null);

    function submit(event: FormEvent) {
        event.preventDefault();

        if (project) {
            // Files cannot be sent with PUT as multipart, so use POST with method spoofing.
            form.transform((values) => ({ ...values, _method: 'put' }));
            form.post(routes.admin.project(project.id), { forceFormData: true });
        } else {
            form.post(routes.admin.projects(), { forceFormData: true });
        }
    }

    return (
        <form onSubmit={submit} noValidate className="grid gap-6 lg:grid-cols-[1fr_20rem]">
            <div className={`${cardClass} space-y-5 p-4 sm:p-6`}>
                <div className="grid gap-5 sm:grid-cols-[1fr_12rem]">
                    <Field id="name" label="Project name" required error={errors.name}>
                        <TextInput
                            id="name"
                            value={data.name}
                            onChange={(event) => setData('name', event.target.value)}
                            maxLength={255}
                            required
                            {...describedBy('name', { error: errors.name })}
                        />
                    </Field>
                    <Field
                        id="project_code"
                        label="Project code"
                        required
                        error={errors.project_code}
                        hint="Used in the public link"
                    >
                        <TextInput
                            id="project_code"
                            value={data.project_code}
                            onChange={(event) => setData('project_code', event.target.value.toUpperCase())}
                            maxLength={50}
                            placeholder="PORTFOLIO-AI"
                            className="font-mono uppercase"
                            required
                            {...describedBy('project_code', { error: errors.project_code, hint: true })}
                        />
                    </Field>
                </div>

                <Field id="description" label="Description" required error={errors.description}>
                    <TextArea
                        id="description"
                        value={data.description}
                        onChange={(event) => setData('description', event.target.value)}
                        rows={5}
                        required
                        {...describedBy('description', { error: errors.description })}
                    />
                </Field>

                <div className="grid gap-5 sm:grid-cols-2">
                    <Field id="demo_url" label="Demo URL" optional error={errors.demo_url} hint="Opened by the Try System button">
                        <TextInput
                            id="demo_url"
                            type="url"
                            inputMode="url"
                            value={data.demo_url}
                            onChange={(event) => setData('demo_url', event.target.value)}
                            placeholder="https://demo.example.com"
                            {...describedBy('demo_url', { error: errors.demo_url, hint: true })}
                        />
                    </Field>
                    <Field id="repository_url" label="Repository URL" optional error={errors.repository_url}>
                        <TextInput
                            id="repository_url"
                            type="url"
                            inputMode="url"
                            value={data.repository_url}
                            onChange={(event) => setData('repository_url', event.target.value)}
                            placeholder="https://github.com/…"
                            {...describedBy('repository_url', { error: errors.repository_url })}
                        />
                    </Field>
                </div>

                <div className="grid gap-5 sm:grid-cols-2">
                    <Field
                        id="technology_stack"
                        label="Technology stack"
                        optional
                        error={errors.technology_stack}
                        hint="Separate with commas"
                    >
                        <TextInput
                            id="technology_stack"
                            value={data.technology_stack}
                            onChange={(event) => setData('technology_stack', event.target.value)}
                            maxLength={255}
                            placeholder="Laravel, React, PostgreSQL"
                            {...describedBy('technology_stack', { error: errors.technology_stack, hint: true })}
                        />
                    </Field>
                    <Field id="presentation_date" label="Presentation date" optional error={errors.presentation_date}>
                        <TextInput
                            id="presentation_date"
                            type="date"
                            value={data.presentation_date}
                            onChange={(event) => setData('presentation_date', event.target.value)}
                            {...describedBy('presentation_date', { error: errors.presentation_date })}
                        />
                    </Field>
                </div>
            </div>

            <div className="space-y-6">
                <div className={`${cardClass} space-y-5 p-4 sm:p-6`}>
                    <Field
                        id="status"
                        label="Status"
                        required
                        error={errors.status}
                        hint="Draft is hidden. Published accepts bug reports. Closed stays visible without new reports."
                    >
                        <Select
                            id="status"
                            value={data.status}
                            onChange={(event) => setData('status', event.target.value as ProjectStatus)}
                            {...describedBy('status', { error: errors.status, hint: true })}
                        >
                            {PROJECT_STATUSES.map((status) => (
                                <option key={status.value} value={status.value}>
                                    {status.label}
                                </option>
                            ))}
                        </Select>
                    </Field>

                    <div>
                        <div className="flex items-center justify-between gap-3">
                            <label htmlFor="bug_reporting_enabled" className="text-sm font-medium text-ink">
                                Bug reporting
                            </label>
                            <Switch
                                id="bug_reporting_enabled"
                                label="Bug reporting"
                                checked={data.bug_reporting_enabled}
                                onChange={(checked) => setData('bug_reporting_enabled', checked)}
                                describedBy="bug_reporting_enabled-hint"
                            />
                        </div>
                        <p id="bug_reporting_enabled-hint" className="mt-0.5 text-xs text-ink-secondary">
                            {data.bug_reporting_enabled
                                ? 'On: visitors can report bugs while the project is Published.'
                                : 'Off: the project stays visible, but the Report Bug form is closed.'}
                        </p>
                        {errors.bug_reporting_enabled && (
                            <p className="mt-1.5 text-sm text-danger">{errors.bug_reporting_enabled}</p>
                        )}
                    </div>

                    <Field id="image" label="Project image" optional error={errors.image} hint="PNG, JPG or WEBP, up to 5 MB">
                        <div className="space-y-3">
                            {currentImage && (
                                <img src={currentImage} alt="" className="aspect-[16/9] w-full rounded-lg object-cover ring-1 ring-hairline" />
                            )}
                            <label htmlFor="image" className={buttonClass('secondary', 'sm', 'w-full')}>
                                <ImagePlus className="size-4" aria-hidden="true" />
                                {currentImage ? 'Replace image' : 'Upload image'}
                            </label>
                            <input
                                id="image"
                                type="file"
                                accept="image/png,image/jpeg,image/webp"
                                className="sr-only"
                                onChange={(event) => {
                                    setData((values) => ({ ...values, image: event.target.files?.[0] ?? null, remove_image: false }));
                                    event.target.value = '';
                                }}
                                {...describedBy('image', { error: errors.image, hint: true })}
                            />
                            {(data.image || (project?.image_url && !data.remove_image)) && (
                                <button
                                    type="button"
                                    onClick={() => setData((values) => ({ ...values, image: null, remove_image: Boolean(project?.image_url) }))}
                                    className={buttonClass('ghost', 'sm', 'w-full')}
                                >
                                    Remove image
                                </button>
                            )}
                        </div>
                    </Field>
                </div>

                <div className="flex flex-col gap-2">
                    <button type="submit" disabled={processing} className={buttonClass('primary', 'lg', 'w-full')}>
                        {processing && <LoaderCircle className="size-5 animate-spin" aria-hidden="true" />}
                        {project ? 'Save changes' : 'Create project'}
                    </button>
                    <Link
                        href={project ? routes.admin.project(project.id) : routes.admin.projects()}
                        className={buttonClass('ghost', 'lg', 'w-full')}
                    >
                        Cancel
                    </Link>
                </div>
            </div>
        </form>
    );
}
