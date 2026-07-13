import * as Dialog from '@radix-ui/react-dialog'
import { Button } from './button'

export interface ConfirmDialogProps {
  open: boolean
  title: string
  message?: string
  confirmLabel?: string
  destructive?: boolean
  onConfirm: () => void
  onCancel: () => void
}

export function ConfirmDialog({
  open,
  title,
  message,
  confirmLabel = 'Delete',
  destructive = true,
  onConfirm,
  onCancel,
}: ConfirmDialogProps) {
  return (
    <Dialog.Root open={open} onOpenChange={v => !v && onCancel()}>
      <Dialog.Portal>
        <Dialog.Overlay className="ps-backdrop fixed inset-0 z-50 bg-black/50" />
        <Dialog.Content
          aria-modal="true"
          className="fixed inset-0 z-50 flex items-center justify-center p-4"
          style={{ pointerEvents: 'none' }}
        >
          <div
            className="ps-card ps-page relative w-full max-w-[360px] p-5"
            style={{ pointerEvents: 'auto' }}
          >
            <Dialog.Title className="font-semibold text-base mb-1">{title}</Dialog.Title>
            {message && (
              <Dialog.Description className="text-text-secondary text-sm mb-5">
                {message}
              </Dialog.Description>
            )}
            <div className="flex gap-2 justify-end">
              <Button variant="secondary" size="sm" onClick={onCancel}>
                Cancel
              </Button>
              <Button
                variant={destructive ? 'destructive' : 'primary'}
                size="sm"
                onClick={onConfirm}
              >
                {confirmLabel}
              </Button>
            </div>
          </div>
        </Dialog.Content>
      </Dialog.Portal>
    </Dialog.Root>
  )
}
