import { api } from './client'
import { toUser } from './transformers'
import type { User } from './types'

export async function getProfile(): Promise<User> {
  const { data } = await api.get('/user')
  return toUser(data.data)
}

interface UpdateProfilePayload {
  first_name?: string
  last_name?: string
  email?: string
  measurement_system?: string
  theme?: string
  current_password?: string
  password?: string
  password_confirmation?: string
}

export async function updateProfile(payload: UpdateProfilePayload): Promise<User> {
  const { data } = await api.put('/user', payload)
  return toUser(data.data)
}
