import { useApp } from '@/lib/use-app'
import { Check } from 'lucide-react'

export interface Toast {
  id: string
  message: string
}

export function Toaster() {
  const { toasts } = useApp()

  return (
    <div
      className="fixed left-1/2 -translate-x-1/2 z-[60] flex flex-col items-center gap-2"
      style={{ bottom: 'calc(env(safe-area-inset-bottom, 0px) + 84px)' }}
    >
      {toasts.map(t => (
        <div
          key={t.id}
          className="ps-page ps-card px-4 py-2.5 text-sm font-medium flex items-center gap-2"
          style={{ boxShadow: '0 6px 20px rgba(0,0,0,.16)' }}
        >
          <Check size={16} className="text-success" />
          {t.message}
        </div>
      ))}
    </div>
  )
}
