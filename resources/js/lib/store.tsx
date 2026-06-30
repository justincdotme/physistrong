import { useCallback, useState, type ReactNode } from 'react'
import { AppContext, type AppContextValue } from './app-context'

let _id = 1000
function uid(p: string): string {
  return `${p}-${++_id}-${Math.floor(Math.random() * 1e4)}`
}

interface Toast {
  id: string
  message: string
}

export function AppProvider({ children }: { children: ReactNode }) {
  const [toasts, setToasts] = useState<Toast[]>([])

  const toast = useCallback((message: string) => {
    const id = uid('toast')
    setToasts(t => [...t, { id, message }])
    setTimeout(() => setToasts(t => t.filter(x => x.id !== id)), 2400)
  }, [])

  const value: AppContextValue = { toasts, toast }

  return <AppContext.Provider value={value}>{children}</AppContext.Provider>
}
