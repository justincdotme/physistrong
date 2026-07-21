import { cn } from '@/lib/utils'

export interface SegmentedControlOption {
  value: string
  label: string
}

export interface SegmentedControlProps {
  options: (string | SegmentedControlOption)[]
  value: string
  onChange: (value: string) => void
  size?: 'sm' | 'md'
  className?: string
}

export function SegmentedControl({
  options,
  value,
  onChange,
  size = 'md',
  className,
}: SegmentedControlProps) {
  const pad = size === 'sm' ? 'min-h-[36px] text-[13px]' : 'min-h-[44px] text-sm'

  return (
    <div
      className={cn('inline-flex p-1 rounded-lg bg-surface-muted gap-1 w-full', className)}
      role="tablist"
    >
      {options.map(o => {
        const v = typeof o === 'string' ? o : o.value
        const label = typeof o === 'string' ? o : o.label
        const active = v === value

        return (
          <button
            key={v}
            role="tab"
            aria-selected={active}
            onClick={() => onChange(v)}
            className={cn(
              'flex-1 px-3 rounded-md font-semibold transition-colors',
              pad,
              active
                ? 'bg-surface-card text-text-primary shadow-sm'
                : 'text-text-secondary hover:text-text-primary'
            )}
          >
            {label}
          </button>
        )
      })}
    </div>
  )
}
