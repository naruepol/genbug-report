import { classNames } from './format';

type ButtonVariant = 'primary' | 'secondary' | 'danger' | 'ghost';
type ButtonSize = 'sm' | 'md' | 'lg';

const variants: Record<ButtonVariant, string> = {
    primary: 'bg-brand text-white shadow-sm hover:bg-brand-hover',
    secondary: 'bg-surface text-ink shadow-sm ring-1 ring-inset ring-hairline hover:bg-page',
    danger: 'bg-danger text-white shadow-sm hover:bg-danger-hover',
    ghost: 'text-ink-secondary hover:bg-chip hover:text-ink',
};

const sizes: Record<ButtonSize, string> = {
    sm: 'gap-1.5 rounded-md px-2.5 py-1.5 text-xs',
    md: 'gap-2 rounded-lg px-3.5 py-2 text-sm',
    lg: 'gap-2 rounded-lg px-5 py-3 text-base',
};

export function buttonClass(variant: ButtonVariant = 'primary', size: ButtonSize = 'md', className?: string): string {
    return classNames(
        'inline-flex items-center justify-center font-semibold whitespace-nowrap transition-colors disabled:cursor-not-allowed disabled:opacity-60',
        variants[variant],
        sizes[size],
        className,
    );
}

export const inputClass =
    'block w-full rounded-lg border-0 bg-surface px-3 py-2 text-base text-ink shadow-xs ring-1 ring-hairline ring-inset placeholder:text-ink-muted focus:ring-2 focus:ring-brand focus:outline-none aria-[invalid=true]:ring-2 aria-[invalid=true]:ring-danger disabled:bg-page sm:text-sm';

export const cardClass = 'rounded-xl bg-surface ring-1 ring-hairline';
