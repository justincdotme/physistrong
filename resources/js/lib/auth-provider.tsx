import { useCallback, useState, useEffect, type ReactNode } from 'react'
import { useQueryClient } from '@tanstack/react-query'
import { useNavigate } from 'react-router-dom'
import { hasToken, clearToken } from '@/api/client'
import { getProfile } from '@/api/user'
import { logout as logoutApi } from '@/api/auth'
import { AuthContext } from './auth-context'
import type { User } from '@/api/types'

export function AuthProvider({ children }: { children: ReactNode }) {
  const [user, setUser] = useState<User | null>(null)
  const [isLoading, setIsLoading] = useState(hasToken())
  const queryClient = useQueryClient()
  const navigate = useNavigate()

  useEffect(() => {
    if (!hasToken()) return
    getProfile()
      .then(setUser)
      .catch(() => {
        clearToken()
        setUser(null)
      })
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
    await logoutApi()
    setUser(null)
    queryClient.clear()
    navigate('/login')
  }, [queryClient, navigate])

  return (
    <AuthContext.Provider value={{ user, isLoading, setUser, handleLogout }}>
      {children}
    </AuthContext.Provider>
  )
}
