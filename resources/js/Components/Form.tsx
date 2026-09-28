import { classNames } from '@/lib/format';
import { inputClass } from '@/lib/ui';
import { ChevronDown } from 'lucide-react';
import type { InputHTMLAttributes, ReactNode, SelectHTMLAttributes, TextareaHTMLAttributes } from 'react';

interface FieldProps {
    id: string;
    label: string;
    error?: string;
    hint?: ReactNode;
    required?: boolean;
    optional?: boolean;
    className?: string;
    children: ReactNode;
}

/** Label, optional hint, the control, and the validation error shown under it. */
export function Field({ id, label, error, hint, required, optional, className, children }: FieldProps) {
    return (
        <div className={className}>
            <label htmlFor={id} className="block text-sm font-medium text-ink">
                {label}
                {required && (
                    <span className="text-status-critical" aria-hidden="true">
                        {' '}
                        *
                    </span>
                )}
                {optional && <span className="font-normal text-ink-muted"> (optional)</span>}
            </label>
            {hint && (
                <p id={`${id}-hint`} className="mt-0.5 text-xs text-ink-secondary">
                    {hint}
                </p>
            )}
            <div className="mt-1.5">{children}</div>
            <FieldError id={id} message={error} />
        </div>
    );
}

export function FieldError({ id, message }: { id: string; message?: string }) {
    if (!message) {
        return null;
    }

    return (
        <p id={`${id}-error`} className="mt-1.5 text-sm text-danger">
            {message}
        </p>
    );
}

/** aria attributes connecting a control with its hint and error message. */
export function describedBy(id: string, { error, hint }: { error?: string; hint?: boolean }) {
    const ids = [hint && `${id}-hint`, error && `${id}-error`].filter(Boolean).join(' ');

    return {
        'aria-invalid': error ? true : undefined,
        'aria-describedby': ids || undefined,
    } as const;
}

export function TextInput({ className, ...props }: InputHTMLAttributes<HTMLInputElement>) {
    return <input {...props} className={classNames(inputClass, className)} />;
}

export function TextArea({ className, rows = 4, ...props }: TextareaHTMLAttributes<HTMLTextAreaElement>) {
    return <textarea rows={rows} {...props} className={classNames(inputClass, 'resize-y', className)} />;
}

export function Select({ className, children, ...props }: SelectHTMLAttributes<HTMLSelectElement>) {
    return (
        <div className={classNames('relative', className)}>
            <select {...props} className={classNames(inputClass, 'appearance-none pr-9')}>
                {children}
            </select>
            <ChevronDown
                className="pointer-events-none absolute top-1/2 right-3 size-4 -translate-y-1/2 text-ink-muted"
                aria-hidden="true"
            />
        </div>
    );
}
