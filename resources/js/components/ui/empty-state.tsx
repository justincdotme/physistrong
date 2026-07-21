import { Dumbbell, type LucideIcon } from 'lucide-react'
import { cn } from '@/lib/utils'

const iconMap: Record<string, LucideIcon> = {
  dumbbell: Dumbbell,
}

export interface EmptyStateProps {
  icon?: string
  title?: string
  children: React.ReactNode
  action?: React.ReactNode
  className?: string
}

export function EmptyState({
  icon = 'dumbbell',
  title,
  children,
  action,
  className,
}: EmptyStateProps) {
  const IconComponent = iconMap[icon] || Dumbbell

  return (
    <div className={cn('ps-card flex flex-col items-center text-center px-6 py-12', className)}>
      <div
        className="h-14 w-14 rounded-full flex items-center justify-center mb-4"
        style={{
          background: 'color-mix(in srgb, var(--color-primary) 12%, transparent)',
          color: 'var(--color-primary)',
        }}
      >
        <IconComponent size={26} />
      </div>
      {title && <p className="font-semibold text-base mb-1">{title}</p>}
      <p className="text-text-secondary text-sm max-w-[280px]">{children}</p>
      {action && <div className="mt-5">{action}</div>}
    </div>
  )
}
