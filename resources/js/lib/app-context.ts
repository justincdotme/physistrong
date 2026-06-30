import { createContext } from 'react'

interface Toast {
  id: string
  message: string
}

export interface AppContextValue {
  toasts: Toast[]
  toast: (message: string) => void
}

export const AppContext = createContext<AppContextValue | null>(null)
