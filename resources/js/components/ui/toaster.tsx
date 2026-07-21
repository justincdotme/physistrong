import { useApp } from '@/lib/use-app'
import { AlertCircle, Check } from 'lucide-react'

export function Toaster() {
  const { toasts } = useApp()

  return (
    <div
      role="status"
      aria-live="polite"
      className="pointer-events-none fixed left-1/2 -translate-x-1/2 z-[60] flex flex-col items-center gap-2 bottom-[calc(env(safe-area-inset-bottom,0px)+84px)] md:bottom-6"
    >
      {toasts.map(t => (
        <div
          key={t.id}
          className="ps-page ps-card px-4 py-2.5 text-sm font-medium flex items-center gap-2"
          style={{ boxShadow: '0 6px 20px rgba(0,0,0,.16)' }}
        >
          {t.variant === 'error' ? (
            <AlertCircle size={16} className="text-destructive" />
          ) : (
            <Check size={16} className="text-success" />
          )}
          {t.message}
        </div>
      ))}
    </div>
  )
}
