import { useEffect, type ReactNode } from 'react'
import { createPortal } from 'react-dom'
import { X } from 'lucide-react'
import { cn } from '@/lib/utils'

export interface SheetProps {
  open: boolean
  onClose: () => void
  title: string
  children: ReactNode
  footer?: ReactNode
  maxHeight?: string
}

export function Sheet({ open, onClose, title, children, footer, maxHeight = '85vh' }: SheetProps) {
  useEffect(() => {
    if (!open) return

    const handleKeyDown = (e: KeyboardEvent) => {
      if (e.key === 'Escape') {
        onClose()
      }
    }

    window.addEventListener('keydown', handleKeyDown)
    document.body.style.overflow = 'hidden'

    return () => {
      window.removeEventListener('keydown', handleKeyDown)
      document.body.style.overflow = ''
    }
  }, [open, onClose])

  if (!open) return null

  return createPortal(
    <div
      className={cn(
        'fixed inset-0 z-50 flex flex-col justify-end md:items-center md:justify-center md:p-4'
      )}
    >
      <div
        className="ps-backdrop absolute inset-0 bg-black/50"
        role="presentation"
        onClick={onClose}
        onKeyDown={e => {
          if (e.key === 'Escape') onClose()
        }}
      />
      <div
        className={cn('ps-sheet relative w-full md:max-w-[440px] bg-surface-card flex flex-col')}
        style={{
          borderTopLeftRadius: 16,
          borderTopRightRadius: 16,
          borderBottomLeftRadius: 0,
          borderBottomRightRadius: 0,
          maxHeight,
          boxShadow: '0 -8px 30px rgba(0,0,0,.18)',
        }}
      >
        <div className="md:rounded-2xl flex flex-col min-h-0" style={{ maxHeight }}>
          <div className="flex items-center justify-between px-5 pt-4 pb-3 border-b border-border shrink-0">
            <div className="md:hidden absolute left-1/2 -translate-x-1/2 top-2 h-1 w-10 rounded-full bg-border-strong" />
            <h3 className="font-semibold text-base mt-1">{title}</h3>
            <button
              onClick={onClose}
              aria-label="Close"
              className="h-9 w-9 -mr-2 flex items-center justify-center rounded-lg text-text-secondary hover:bg-surface-muted"
            >
              <X size={20} />
            </button>
          </div>
          <div className="overflow-y-auto px-5 py-4 min-h-0 flex-1">{children}</div>
          {footer && (
            <div
              className="px-5 py-3 border-t border-border shrink-0"
              style={{ paddingBottom: 'calc(env(safe-area-inset-bottom, 0px) + 12px)' }}
            >
              {footer}
            </div>
          )}
        </div>
      </div>
    </div>,
    document.body
  )
}
