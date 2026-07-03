import { api, markAuthenticated, markUnauthenticated } from './client'
import { toUser } from './transformers'
import type { User } from './types'

interface AuthResponse {
  user: User
}

interface LoginPayload {
  email: string
  password: string
}

interface RegisterPayload {
  email: string
  password: string
  password_confirmation: string
  measurement_system: string
  first_name?: string
  last_name?: string
}

export async function login(payload: LoginPayload): Promise<AuthResponse> {
  const { data } = await api.post('/login', payload)
  markAuthenticated()
  return { user: toUser(data.user) }
}

export async function register(payload: RegisterPayload): Promise<AuthResponse> {
  const { data } = await api.post('/register', payload)
  markAuthenticated()
  return { user: toUser(data.user) }
}

export async function logout(): Promise<void> {
  await api.post('/logout')
  markUnauthenticated()
}

export async function forgotPassword(email: string): Promise<void> {
  await api.post('/password/forgot', { email })
}

export async function resetPassword(payload: {
  email: string
  token: string
  password: string
  password_confirmation: string
}): Promise<void> {
  await api.post('/password/reset', payload)
}
