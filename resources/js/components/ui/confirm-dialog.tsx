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
  if (!open) return null

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div
        className="ps-backdrop absolute inset-0 bg-black/50"
        role="presentation"
        onClick={onCancel}
        onKeyDown={e => {
          if (e.key === 'Escape') onCancel()
        }}
      />
      <div className="ps-card ps-page relative w-full max-w-[360px] p-5">
        <h3 className="font-semibold text-base mb-1">{title}</h3>
        {message && <p className="text-text-secondary text-sm mb-5">{message}</p>}
        <div className="flex gap-2 justify-end">
          <Button variant="secondary" size="sm" onClick={onCancel}>
            Cancel
          </Button>
          <Button variant={destructive ? 'destructive' : 'primary'} size="sm" onClick={onConfirm}>
            {confirmLabel}
          </Button>
        </div>
      </div>
    </div>
  )
}
