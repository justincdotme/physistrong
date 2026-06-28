import { forwardRef, type ButtonHTMLAttributes } from 'react'
import { cva, type VariantProps } from 'class-variance-authority'
import { cn } from '@/lib/utils'

const buttonVariants = cva(
  'inline-flex items-center justify-center gap-2 font-semibold select-none transition-colors disabled:cursor-not-allowed',
  {
    variants: {
      variant: {
        primary: 'ps-btn-primary',
        secondary:
          'bg-surface-card text-text-primary border border-border-strong hover:bg-surface-muted rounded-lg',
        ghost: 'text-text-secondary hover:bg-surface-muted hover:text-text-primary rounded-lg',
        destructive: 'bg-destructive text-white hover:brightness-110 rounded-lg',
      },
      size: {
        sm: 'text-[13px] px-3 min-h-[36px] rounded-lg',
        md: 'text-sm px-4 min-h-[44px] rounded-lg',
        lg: 'text-sm px-5 min-h-[48px] rounded-lg',
      },
    },
    defaultVariants: { variant: 'primary', size: 'md' },
  }
)

export interface ButtonProps
  extends ButtonHTMLAttributes<HTMLButtonElement>, VariantProps<typeof buttonVariants> {
  icon?: React.ReactNode
  full?: boolean
}

export const Button = forwardRef<HTMLButtonElement, ButtonProps>(
  ({ className, variant, size, icon, full, children, ...props }, ref) => {
    return (
      <button
        ref={ref}
        className={cn(buttonVariants({ variant, size }), full && 'w-full', className)}
        {...props}
      >
        {icon}
        {children}
      </button>
    )
  }
)
Button.displayName = 'Button'
