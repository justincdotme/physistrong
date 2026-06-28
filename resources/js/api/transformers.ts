import type { User, EquipmentType } from '@/api/types'
import type { MeasurementSystem } from '@/lib/units'

export interface RawUser {
  id: number
  email: string
  first_name: string | null
  last_name: string | null
  measurement_system: MeasurementSystem
  theme: 'light' | 'dark' | 'system'
}

export interface RawEquipmentType {
  id: number
  name: string
  is_system: boolean
}

export function toUser(raw: RawUser): User {
  return {
    id: String(raw.id),
    firstName: raw.first_name ?? '',
    lastName: raw.last_name ?? '',
    email: raw.email,
    measurementSystem: raw.measurement_system,
    theme: raw.theme,
  }
}

export function toEquipmentType(raw: RawEquipmentType): EquipmentType {
  return {
    id: String(raw.id),
    name: raw.name,
    isSystem: raw.is_system,
  }
}
