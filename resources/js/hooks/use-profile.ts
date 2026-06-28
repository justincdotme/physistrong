import { useApp } from '@/lib/use-app'
import type { User } from '@/api/types'

interface UseProfileResult {
  data: User
  loading: boolean
  error: null
}

export function useProfile(): UseProfileResult {
  const { user } = useApp()
  return { data: user, loading: false, error: null }
}
