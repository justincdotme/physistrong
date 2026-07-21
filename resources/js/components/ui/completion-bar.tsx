import { cn } from '@/lib/utils'

export interface CompletionBarProps {
  ratio: number
  height?: number
  className?: string
}

export function CompletionBar({ ratio, height = 4, className }: CompletionBarProps) {
  const percent = Math.round(ratio * 100)

  return (
    <div
      className={cn('w-full overflow-hidden', className)}
      style={{
        height,
        background: 'var(--color-surface-muted)',
        borderRadius: 999,
      }}
      role="progressbar"
      aria-valuenow={percent}
      aria-valuemin={0}
      aria-valuemax={100}
    >
      <div
        style={{
          width: `${percent}%`,
          height: '100%',
          background: 'var(--color-primary)',
          transition: 'width .1s ease-out',
        }}
      />
    </div>
  )
}
