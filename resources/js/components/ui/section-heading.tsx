import { cn } from '@/lib/utils'

export interface SectionHeadingProps {
  children: React.ReactNode
  action?: React.ReactNode
  className?: string
}

export function SectionHeading({ children, action, className }: SectionHeadingProps) {
  return (
    <div className={cn('flex items-center justify-between mb-3 mt-1', className)}>
      <h2 style={{ fontSize: 18, fontWeight: 600 }}>{children}</h2>
      {action}
    </div>
  )
}
