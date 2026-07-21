import { createContext } from 'react'
import type { User } from '@/api/types'

export interface AuthContextValue {
  user: User | null
  isLoading: boolean
  setUser: (user: User) => void
  handleLogout: () => Promise<void>
}

export const AuthContext = createContext<AuthContextValue | null>(null)
