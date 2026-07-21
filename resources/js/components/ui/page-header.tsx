import type { ReactNode } from 'react'
import { ChevronLeft } from 'lucide-react'
import { cn } from '@/lib/utils'

export interface PageHeaderProps {
  title: string | ReactNode
  subtitle?: string
  back?: boolean
  onBack?: () => void
  actions?: ReactNode
  className?: string
}

export function PageHeader({ title, subtitle, back, onBack, actions, className }: PageHeaderProps) {
  return (
    <div className={cn('mb-5', className)}>
      <div className="flex items-center gap-3">
        {back && (
          <button
            dusk="back"
            onClick={onBack}
            aria-label="Go back"
            className="shrink-0 -ml-2 h-11 w-11 flex items-center justify-center rounded-lg text-text-secondary hover:bg-surface-muted hover:text-text-primary"
          >
            <ChevronLeft size={24} />
          </button>
        )}
        <h1
          className="truncate min-w-0 flex-1 py-px"
          style={{ fontSize: 24, fontWeight: 700, letterSpacing: '-0.5px' }}
        >
          {title}
        </h1>
        {actions && <div className="flex items-center gap-2 shrink-0">{actions}</div>}
      </div>
      {subtitle && (
        <p className={cn('text-text-secondary text-sm mt-0.5', back && 'ml-12')}>{subtitle}</p>
      )}
    </div>
  )
}
