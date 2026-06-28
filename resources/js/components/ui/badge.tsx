import { cn } from '@/lib/utils'

export interface BadgeProps {
  tone?: 'neutral' | 'primary' | 'secondary' | 'success' | 'accent'
  soft?: boolean
  children: React.ReactNode
  className?: string
}

const BADGE_TONES = {
  neutral: 'bg-surface-muted text-text-secondary',
  primary: 'text-primary',
  accent: 'text-accent',
  secondary: 'text-secondary',
  success: 'text-success',
}

export function Badge({ tone = 'neutral', soft: _soft, children, className }: BadgeProps) {
  const style =
    tone !== 'neutral'
      ? {
          color: `var(--color-${tone})`,
          background: `color-mix(in srgb, var(--color-${tone}) 12%, transparent)`,
        }
      : undefined

  return (
    <span
      className={cn(
        'inline-flex items-center gap-1 px-2 py-0.5 rounded-full label-caps',
        tone === 'neutral' && BADGE_TONES.neutral,
        className
      )}
      style={style}
    >
      {tone === 'neutral' && <span className="label-caps text-text-secondary">{children}</span>}
      {tone !== 'neutral' && children}
    </span>
  )
}
