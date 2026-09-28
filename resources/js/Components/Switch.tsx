import { classNames } from '@/lib/format';

interface SwitchProps {
    checked: boolean;
    onChange: (checked: boolean) => void;
    /** Accessible name, e.g. "Bug reporting". */
    label: string;
    id?: string;
    disabled?: boolean;
    describedBy?: string;
    size?: 'sm' | 'md';
}

/** An on/off toggle (role="switch"). */
export default function Switch({ checked, onChange, label, id, disabled = false, describedBy, size = 'md' }: SwitchProps) {
    const small = size === 'sm';

    return (
        <button
            type="button"
            role="switch"
            id={id}
            aria-checked={checked}
            aria-label={label}
            aria-describedby={describedBy}
            disabled={disabled}
            onClick={() => onChange(!checked)}
            className={classNames(
                'relative inline-flex shrink-0 items-center rounded-full transition-colors disabled:cursor-not-allowed disabled:opacity-60',
                small ? 'h-5 w-9' : 'h-6 w-11',
                checked ? 'bg-brand' : 'bg-ink-muted',
            )}
        >
            <span
                aria-hidden="true"
                className={classNames(
                    'inline-block rounded-full bg-white shadow-sm transition-transform',
                    small ? 'size-4' : 'size-5',
                    checked ? (small ? 'translate-x-4.5' : 'translate-x-5.5') : 'translate-x-0.5',
                )}
            />
        </button>
    );
}
