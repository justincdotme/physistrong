import { type ButtonHTMLAttributes } from 'react'
import { cn } from '@/lib/utils'

const variantClasses = {
  primary: 'ps-btn-primary',
  secondary:
    'bg-surface-card text-text-primary border border-border-strong hover:bg-surface-muted rounded-lg',
  ghost: 'text-text-secondary hover:bg-surface-muted hover:text-text-primary rounded-lg',
  destructive: 'bg-destructive text-white hover:brightness-110 rounded-lg',
} as const

const sizeClasses = {
  sm: 'text-[13px] px-3 min-h-[36px] rounded-lg',
  md: 'text-sm px-4 min-h-[44px] rounded-lg',
  lg: 'text-sm px-5 min-h-[48px] rounded-lg',
} as const

export interface ButtonProps extends ButtonHTMLAttributes<HTMLButtonElement> {
  variant?: keyof typeof variantClasses
  size?: keyof typeof sizeClasses
  icon?: React.ReactNode
  full?: boolean
  ref?: React.Ref<HTMLButtonElement>
}

export function Button({
  className,
  variant = 'primary',
  size = 'md',
  icon,
  full,
  children,
  ref,
  ...props
}: ButtonProps) {
  return (
    <button
      ref={ref}
      className={cn(
        'inline-flex items-center justify-center gap-2 font-semibold select-none transition-colors disabled:cursor-not-allowed',
        variantClasses[variant],
        sizeClasses[size],
        full && 'w-full',
        className
      )}
      {...props}
    >
      {icon}
      {children}
    </button>
  )
}
