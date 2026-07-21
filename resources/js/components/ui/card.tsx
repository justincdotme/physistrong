import { type HTMLAttributes, type ReactNode } from 'react'
import { cn } from '@/lib/utils'

export interface CardProps extends HTMLAttributes<HTMLDivElement> {
  onClick?: () => void
  children: ReactNode
  ref?: React.Ref<HTMLDivElement>
}

export function Card({ className, onClick, children, ref, ...props }: CardProps) {
  const handleKeyDown = (e: React.KeyboardEvent) => {
    if (e.key === 'Enter' || e.key === ' ') {
      e.preventDefault()
      onClick?.()
    }
  }

  if (onClick) {
    return (
      <div
        ref={ref}
        role="button"
        tabIndex={0}
        onClick={onClick}
        onKeyDown={handleKeyDown}
        className={cn(
          'ps-card text-left w-full cursor-pointer transition-transform active:scale-[.995]',
          className
        )}
        {...props}
      >
        {children}
      </div>
    )
  }

  return (
    <div ref={ref} className={cn('ps-card text-left w-full', className)} {...props}>
      {children}
    </div>
  )
}
