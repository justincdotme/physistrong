import { api, setToken, clearToken } from './client'
import { toUser } from './transformers'
import type { User } from './types'

interface AuthResponse {
  user: User
  token: string
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
  const user = toUser(data.user)
  setToken(data.token)
  return { user, token: data.token }
}

export async function register(payload: RegisterPayload): Promise<AuthResponse> {
  const { data } = await api.post('/register', payload)
  const user = toUser(data.user)
  setToken(data.token)
  return { user, token: data.token }
}

export async function logout(): Promise<void> {
  try {
    await api.post('/logout')
  } finally {
    clearToken()
  }
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
