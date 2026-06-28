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
    <div className={cn('flex items-start gap-3 mb-5', className)}>
      {back && (
        <button
          onClick={onBack}
          aria-label="Go back"
          className="shrink-0 -ml-2 mt-0.5 h-11 w-11 flex items-center justify-center rounded-lg text-text-secondary hover:bg-surface-muted hover:text-text-primary"
        >
          <ChevronLeft size={24} />
        </button>
      )}
      <div className="min-w-0 flex-1">
        <h1 className="truncate" style={{ fontSize: 24, fontWeight: 700, letterSpacing: '-0.5px' }}>
          {title}
        </h1>
        {subtitle && <p className="text-text-secondary text-sm mt-0.5">{subtitle}</p>}
      </div>
      {actions && <div className="flex items-center gap-2 shrink-0">{actions}</div>}
    </div>
  )
}
