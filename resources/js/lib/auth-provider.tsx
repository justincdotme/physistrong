import { useCallback, useState, useEffect, type ReactNode } from 'react'
import { useQueryClient } from '@tanstack/react-query'
import { useNavigate } from 'react-router-dom'
import { markAuthenticated } from '@/api/client'
import { getProfile } from '@/api/user'
import { logout as logoutApi } from '@/api/auth'
import { AuthContext } from './auth-context'
import { useApp } from './use-app'
import type { User } from '@/api/types'

export function AuthProvider({ children }: { children: ReactNode }) {
  const [user, setUser] = useState<User | null>(null)
  // The token is an HttpOnly cookie the client cannot inspect, so the only
  // way to learn the auth state is to ask the API.
  const [isLoading, setIsLoading] = useState(true)
  const queryClient = useQueryClient()
  const navigate = useNavigate()
  const { toast } = useApp()

  useEffect(() => {
    getProfile()
      .then(profile => {
        markAuthenticated()
        setUser(profile)
      })
      .catch(() => setUser(null))
      .finally(() => setIsLoading(false))
  }, [])

  const theme = user?.theme
  useEffect(() => {
    if (!theme) return
    let resolved = theme
    if (resolved === 'system') {
      resolved = window.matchMedia?.('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'
    }
    document.documentElement.classList.toggle('dark', resolved === 'dark')

    if (theme !== 'system') return
    const mq = window.matchMedia('(prefers-color-scheme: dark)')
    const onChange = () => {
      const dark = mq.matches
      document.documentElement.classList.toggle('dark', dark)
    }
    mq.addEventListener('change', onChange)
    return () => mq.removeEventListener('change', onChange)
  }, [theme])

  const handleLogout = useCallback(async () => {
    try {
      await logoutApi()
    } catch {
      toast('Could not log out. Try again.', 'error')
      return
    }
    setUser(null)
    queryClient.clear()
    navigate('/login')
  }, [queryClient, navigate, toast])

  return (
    <AuthContext.Provider value={{ user, isLoading, setUser, handleLogout }}>
      {children}
    </AuthContext.Provider>
  )
}
