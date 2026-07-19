import type { ReactNode } from 'react'
import * as Dialog from '@radix-ui/react-dialog'
import { X } from 'lucide-react'

export interface SheetProps {
  open: boolean
  onClose: () => void
  title: string
  children: ReactNode
  footer?: ReactNode
  maxHeight?: string
}

export function Sheet({ open, onClose, title, children, footer, maxHeight = '85vh' }: SheetProps) {
  return (
    <Dialog.Root open={open} onOpenChange={v => !v && onClose()}>
      <Dialog.Portal>
        <Dialog.Overlay className="ps-backdrop fixed inset-0 z-50 bg-black/50" />
        <Dialog.Content
          aria-modal="true"
          className="ps-sheet fixed inset-0 z-50 flex flex-col justify-end md:items-center md:justify-center md:p-4"
          style={{ pointerEvents: 'none' }}
        >
          <div
            className="relative w-full md:max-w-[440px] bg-surface-card flex flex-col"
            style={{
              borderTopLeftRadius: 16,
              borderTopRightRadius: 16,
              borderBottomLeftRadius: 0,
              borderBottomRightRadius: 0,
              maxHeight,
              boxShadow: '0 -8px 30px rgba(0,0,0,.18)',
              pointerEvents: 'auto',
            }}
          >
            <div className="md:rounded-2xl flex flex-col min-h-0" style={{ maxHeight }}>
              <div className="flex items-center justify-between px-5 pt-4 pb-3 border-b border-border shrink-0">
                <div className="md:hidden absolute left-1/2 -translate-x-1/2 top-2 h-1 w-10 rounded-full bg-border-strong" />
                <Dialog.Title className="font-semibold text-base mt-1">{title}</Dialog.Title>
                <Dialog.Close
                  aria-label="Close"
                  className="h-9 w-9 -mr-2 flex items-center justify-center rounded-lg text-text-secondary hover:bg-surface-muted"
                >
                  <X size={20} />
                </Dialog.Close>
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
        </Dialog.Content>
      </Dialog.Portal>
    </Dialog.Root>
  )
}
