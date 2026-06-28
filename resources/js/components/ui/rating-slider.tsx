import { cn } from '@/lib/utils'

export interface RatingSliderProps {
  value: number | null
  onChange: (value: number) => void
  accent?: 'primary' | 'secondary' | 'accent'
  className?: string
}

export function RatingSlider({
  value,
  onChange,
  accent = 'primary',
  className,
}: RatingSliderProps) {
  return (
    <div className={cn('flex items-center gap-3', className)}>
      <input
        type="range"
        min="1"
        max="10"
        step="1"
        value={value ?? 1}
        onChange={e => onChange(Number(e.target.value))}
        className="flex-1 h-2 appearance-none rounded-full bg-surface-muted accent-primary cursor-pointer"
        style={{ accentColor: `var(--color-${accent})` }}
      />
      <span
        className="w-12 text-right tabular-nums"
        style={{
          fontSize: 20,
          fontWeight: 700,
          letterSpacing: '-0.5px',
          color: value == null ? 'var(--color-text-muted)' : `var(--color-${accent})`,
        }}
      >
        {value ?? '—'}
      </span>
    </div>
  )
}
