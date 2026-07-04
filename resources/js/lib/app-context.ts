import { createContext } from 'react'

export type ToastVariant = 'success' | 'error'

export interface Toast {
  id: string
  message: string
  variant: ToastVariant
}

export interface AppContextValue {
  toasts: Toast[]
  toast: (message: string, variant?: ToastVariant) => void
}

export const AppContext = createContext<AppContextValue | null>(null)
