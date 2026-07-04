import { useCallback, useState, type ReactNode } from 'react'
import { AppContext, type AppContextValue, type ToastVariant } from './app-context'
import { Toaster } from '@/components/ui/toaster'

let _id = 1000
function uid(p: string): string {
  return `${p}-${++_id}-${Math.floor(Math.random() * 1e4)}`
}

interface Toast {
  id: string
  message: string
  variant: ToastVariant
}

// Errors linger longer than confirmations so the failure is readable.
const TOAST_MS: Record<ToastVariant, number> = { success: 2400, error: 4000 }

export function AppProvider({ children }: { children: ReactNode }) {
  const [toasts, setToasts] = useState<Toast[]>([])

  const toast = useCallback((message: string, variant: ToastVariant = 'success') => {
    const id = uid('toast')
    setToasts(t => [...t, { id, message, variant }])
    setTimeout(() => setToasts(t => t.filter(x => x.id !== id)), TOAST_MS[variant])
  }, [])

  const value: AppContextValue = { toasts, toast }

  return (
    <AppContext.Provider value={value}>
      {children}
      <Toaster />
    </AppContext.Provider>
  )
}
