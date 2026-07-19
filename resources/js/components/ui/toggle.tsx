import { cn } from '@/lib/utils'

export interface ToggleProps {
  checked: boolean
  onChange: (checked: boolean) => void
  label?: string
  id?: string
}

export function Toggle({ checked, onChange, label, id }: ToggleProps) {
  return (
    <button
      id={id}
      role="switch"
      aria-checked={checked}
      aria-label={label}
      onClick={() => onChange(!checked)}
      className="inline-flex min-h-[44px] min-w-[44px] shrink-0 items-center justify-center"
    >
      <span
        className={cn(
          'relative inline-flex h-[28px] w-[48px] items-center rounded-full transition-colors',
          !checked && 'bg-border-strong'
        )}
        style={checked ? { background: 'var(--color-primary)' } : undefined}
      >
        <span
          className={cn(
            'inline-block h-[22px] w-[22px] transform rounded-full bg-white transition-transform shadow',
            checked ? 'translate-x-[23px]' : 'translate-x-[3px]'
          )}
        />
      </span>
    </button>
  )
}
