import { describedBy, Field, Select, TextArea, TextInput } from '@/Components/Form';
import PublicLayout from '@/Layouts/PublicLayout';
import { CATEGORIES } from '@/lib/enums';
import { detectEnvironment } from '@/lib/environment';
import { formatFileSize } from '@/lib/format';
import { routes } from '@/lib/routes';
import { buttonClass, cardClass } from '@/lib/ui';
import type { BugCategory, Project } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, CircleAlert, ImagePlus, LoaderCircle, ShieldCheck, X } from 'lucide-react';
import { useEffect, useMemo, useState, type FormEvent, type ReactNode } from 'react';

interface CreateBugProps {
    project: Project;
}

const MAX_SCREENSHOT_BYTES = 5 * 1024 * 1024;
const SCREENSHOT_TYPES = ['image/png', 'image/jpeg', 'image/webp'];

export default function CreateBug({ project }: CreateBugProps) {
    const form = useForm({
        title: '',
        description: '',
        category: '' as BugCategory | '',
        steps_to_reproduce: '',
        expected_result: '',
        actual_result: '',
        page_screen: '',
        browser: '',
        operating_system: '',
        device: '',
        screenshot: null as File | null,
        name: '',
        email: '',
        website: '', // honeypot — must stay empty
    });
    const { data, setData, errors, processing, progress } = form;

    // Pre-fill the environment fields once, on the reporter's device.
    useEffect(() => {
        const detected = detectEnvironment();
        form.setData((current) => ({
            ...current,
            browser: current.browser || detected.browser,
            operating_system: current.operating_system || detected.operating_system,
            device: current.device || detected.device,
        }));
    }, []);

    const [screenshotError, setScreenshotError] = useState<string | null>(null);
    const previewUrl = useMemo(() => (data.screenshot ? URL.createObjectURL(data.screenshot) : null), [data.screenshot]);

    useEffect(() => () => {
        if (previewUrl) URL.revokeObjectURL(previewUrl);
    }, [previewUrl]);

    function chooseScreenshot(file: File | null) {
        setScreenshotError(null);

        if (file && !SCREENSHOT_TYPES.includes(file.type)) {
            setScreenshotError('Please choose a PNG, JPG or WEBP image.');
            return;
        }

        if (file && file.size > MAX_SCREENSHOT_BYTES) {
            setScreenshotError(`This image is ${formatFileSize(file.size)}. The limit is 5 MB.`);
            return;
        }

        setData('screenshot', file);
    }

    // After a validation error the page scrolls back to the top, where the error summary is shown.
    function submit(event: FormEvent) {
        event.preventDefault();
        form.post(routes.reportBug(project.project_code));
    }

    const formError = (errors as Record<string, string | undefined>).form;
    const errorCount = Object.keys(errors).filter((key) => key !== 'form').length;

    return (
        <PublicLayout>
            <Head title={`Report a bug · ${project.name}`} />

            <div className="mx-auto max-w-2xl px-4 py-6 sm:py-10">
                <Link
                    href={routes.project(project.project_code)}
                    className="inline-flex items-center gap-1.5 text-sm text-ink-secondary hover:text-ink"
                >
                    <ArrowLeft className="size-4" aria-hidden="true" />
                    {project.name}
                </Link>

                <h1 className="mt-3 text-2xl font-bold tracking-tight text-ink">Report a bug</h1>
                <p className="mt-1 text-sm text-ink-secondary">
                    Tell us what went wrong in <span className="font-medium text-ink">{project.name}</span>. Only the title and
                    description are required.
                </p>

                {(formError || errorCount > 0) && (
                    <div role="alert" className="mt-5 flex gap-3 rounded-xl bg-surface p-4 text-sm text-ink ring-1 ring-status-critical/40">
                        <CircleAlert className="size-5 shrink-0 text-status-critical" aria-hidden="true" />
                        <p>{formError ?? `Please check the ${errorCount === 1 ? 'field' : `${errorCount} fields`} marked below.`}</p>
                    </div>
                )}

                <form onSubmit={submit} noValidate className="mt-6 space-y-5">
                    <Section title="What went wrong?">
                        <Field id="title" label="Bug title" required error={errors.title}>
                            <TextInput
                                id="title"
                                value={data.title}
                                onChange={(event) => setData('title', event.target.value)}
                                maxLength={255}
                                placeholder="e.g. Login button does nothing"
                                required
                                {...describedBy('title', { error: errors.title })}
                            />
                        </Field>

                        <Field id="description" label="Description" required error={errors.description}>
                            <TextArea
                                id="description"
                                value={data.description}
                                onChange={(event) => setData('description', event.target.value)}
                                rows={5}
                                placeholder="What happened? Where were you in the system?"
                                required
                                {...describedBy('description', { error: errors.description })}
                            />
                        </Field>

                        <Field id="category" label="Category" optional error={errors.category}>
                            <Select
                                id="category"
                                value={data.category}
                                onChange={(event) => setData('category', event.target.value as BugCategory | '')}
                                {...describedBy('category', { error: errors.category })}
                            >
                                <option value="">Choose a category</option>
                                {CATEGORIES.map((category) => (
                                    <option key={category.value} value={category.value}>
                                        {category.label}
                                    </option>
                                ))}
                            </Select>
                        </Field>
                    </Section>

                    <Section title="Details" description="Optional, but they help the team reproduce the bug.">
                        <Field id="steps_to_reproduce" label="Steps to reproduce" error={errors.steps_to_reproduce}>
                            <TextArea
                                id="steps_to_reproduce"
                                value={data.steps_to_reproduce}
                                onChange={(event) => setData('steps_to_reproduce', event.target.value)}
                                rows={4}
                                placeholder={'1. Open the login page\n2. Enter an email\n3. Tap "Sign in"'}
                                {...describedBy('steps_to_reproduce', { error: errors.steps_to_reproduce })}
                            />
                        </Field>
                        <div className="grid gap-5 sm:grid-cols-2">
                            <Field id="expected_result" label="Expected result" error={errors.expected_result}>
                                <TextArea
                                    id="expected_result"
                                    value={data.expected_result}
                                    onChange={(event) => setData('expected_result', event.target.value)}
                                    rows={3}
                                    placeholder="What should have happened?"
                                    {...describedBy('expected_result', { error: errors.expected_result })}
                                />
                            </Field>
                            <Field id="actual_result" label="Actual result" error={errors.actual_result}>
                                <TextArea
                                    id="actual_result"
                                    value={data.actual_result}
                                    onChange={(event) => setData('actual_result', event.target.value)}
                                    rows={3}
                                    placeholder="What happened instead?"
                                    {...describedBy('actual_result', { error: errors.actual_result })}
                                />
                            </Field>
                        </div>
                        <Field id="page_screen" label="Page / Screen" error={errors.page_screen}>
                            <TextInput
                                id="page_screen"
                                value={data.page_screen}
                                onChange={(event) => setData('page_screen', event.target.value)}
                                maxLength={255}
                                placeholder="e.g. Checkout page"
                                {...describedBy('page_screen', { error: errors.page_screen })}
                            />
                        </Field>
                    </Section>

                    <Section title="Screenshot" description="PNG, JPG or WEBP, up to 5 MB.">
                        {data.screenshot && previewUrl ? (
                            <div className="flex items-start gap-4">
                                <img src={previewUrl} alt="Selected screenshot preview" className="h-28 w-auto max-w-[60%] rounded-lg object-cover ring-1 ring-hairline" />
                                <div className="min-w-0 flex-1 text-sm">
                                    <p className="truncate font-medium text-ink">{data.screenshot.name}</p>
                                    <p className="text-ink-secondary">{formatFileSize(data.screenshot.size)}</p>
                                    <button
                                        type="button"
                                        onClick={() => chooseScreenshot(null)}
                                        className={buttonClass('ghost', 'sm', 'mt-2 -ml-2')}
                                    >
                                        <X className="size-3.5" aria-hidden="true" />
                                        Remove
                                    </button>
                                </div>
                            </div>
                        ) : (
                            <label
                                htmlFor="screenshot"
                                className="flex cursor-pointer flex-col items-center gap-2 rounded-xl border border-dashed border-baseline bg-page px-4 py-6 text-center hover:border-brand hover:bg-brand-soft/40"
                            >
                                <ImagePlus className="size-7 text-ink-muted" aria-hidden="true" />
                                <span className="text-sm font-medium text-ink">Add a screenshot</span>
                                <span className="text-xs text-ink-secondary">Tap to choose a photo or take one</span>
                            </label>
                        )}
                        <input
                            id="screenshot"
                            type="file"
                            accept="image/png,image/jpeg,image/webp"
                            className="sr-only"
                            onChange={(event) => {
                                chooseScreenshot(event.target.files?.[0] ?? null);
                                event.target.value = '';
                            }}
                            {...describedBy('screenshot', { error: screenshotError ?? errors.screenshot })}
                        />
                        {(screenshotError || errors.screenshot) && (
                            <p id="screenshot-error" className="text-sm text-danger">
                                {screenshotError ?? errors.screenshot}
                            </p>
                        )}
                    </Section>

                    <Section title="Your device" description="Filled in automatically — change it if it is not right.">
                        <div className="grid gap-4 sm:grid-cols-3">
                            <Field id="browser" label="Browser" error={errors.browser}>
                                <TextInput
                                    id="browser"
                                    value={data.browser}
                                    onChange={(event) => setData('browser', event.target.value)}
                                    maxLength={100}
                                    {...describedBy('browser', { error: errors.browser })}
                                />
                            </Field>
                            <Field id="operating_system" label="Operating system" error={errors.operating_system}>
                                <TextInput
                                    id="operating_system"
                                    value={data.operating_system}
                                    onChange={(event) => setData('operating_system', event.target.value)}
                                    maxLength={100}
                                    {...describedBy('operating_system', { error: errors.operating_system })}
                                />
                            </Field>
                            <Field id="device" label="Device" error={errors.device}>
                                <TextInput
                                    id="device"
                                    value={data.device}
                                    onChange={(event) => setData('device', event.target.value)}
                                    maxLength={100}
                                    {...describedBy('device', { error: errors.device })}
                                />
                            </Field>
                        </div>
                    </Section>

                    <Section title="Contact" description="Optional — only if you are happy for the team to follow up.">
                        <p className="flex gap-2 rounded-lg bg-page p-3 text-sm text-ink-secondary ring-1 ring-hairline">
                            <ShieldCheck className="size-5 shrink-0 text-status-good" aria-hidden="true" />
                            <span>
                                Your name and email are visible to the admin only. The public bug board always shows{' '}
                                <span className="font-medium text-ink">Anonymous User</span>.
                            </span>
                        </p>
                        <div className="grid gap-4 sm:grid-cols-2">
                            <Field id="name" label="Name" error={errors.name}>
                                <TextInput
                                    id="name"
                                    value={data.name}
                                    onChange={(event) => setData('name', event.target.value)}
                                    maxLength={100}
                                    autoComplete="name"
                                    {...describedBy('name', { error: errors.name })}
                                />
                            </Field>
                            <Field id="email" label="Email" error={errors.email}>
                                <TextInput
                                    id="email"
                                    type="email"
                                    inputMode="email"
                                    value={data.email}
                                    onChange={(event) => setData('email', event.target.value)}
                                    autoComplete="email"
                                    {...describedBy('email', { error: errors.email })}
                                />
                            </Field>
                        </div>
                    </Section>

                    {/* Honeypot: hidden from people and assistive tech; bots tend to fill it in. */}
                    <div aria-hidden="true" className="absolute -left-[10000px] h-px w-px overflow-hidden">
                        <label htmlFor="website">Website</label>
                        <input
                            id="website"
                            name="website"
                            type="text"
                            tabIndex={-1}
                            autoComplete="off"
                            value={data.website}
                            onChange={(event) => setData('website', event.target.value)}
                        />
                    </div>

                    <div className="flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-end">
                        <Link href={routes.project(project.project_code)} className={buttonClass('ghost', 'lg')}>
                            Cancel
                        </Link>
                        <button type="submit" disabled={processing} className={buttonClass('primary', 'lg', 'w-full sm:w-auto')}>
                            {processing && <LoaderCircle className="size-5 animate-spin" aria-hidden="true" />}
                            {processing && progress ? `Uploading ${progress.percentage ?? 0}%` : 'Submit bug report'}
                        </button>
                    </div>
                </form>
            </div>
        </PublicLayout>
    );
}

function Section({ title, description, children }: { title: string; description?: string; children: ReactNode }) {
    return (
        <fieldset className={`${cardClass} space-y-5 p-4 sm:p-6`}>
            <legend className="sr-only">{title}</legend>
            <div aria-hidden="true">
                <h2 className="text-base font-semibold text-ink">{title}</h2>
                {description && <p className="mt-0.5 text-sm text-ink-secondary">{description}</p>}
            </div>
            {children}
        </fieldset>
    );
}
