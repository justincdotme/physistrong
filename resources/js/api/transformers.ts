import type { User, EquipmentType, Exercise, ExerciseType } from '@/api/types'
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

export interface RawExercise {
  id: number
  name: string
  type: string
  notes: string | null
  user_id: number | null
  equipment_type_id: number | null
  type_attributes: Record<string, unknown> | null
  created_at: string
  updated_at: string
}

export function toExercise(raw: RawExercise): Exercise {
  const exercise: Exercise = {
    id: String(raw.id),
    userId: raw.user_id !== null ? String(raw.user_id) : null,
    name: raw.name,
    type: raw.type as ExerciseType,
    equipmentTypeId: raw.equipment_type_id !== null ? String(raw.equipment_type_id) : null,
    notes: raw.notes,
  }

  const attrs = raw.type_attributes
  if (!attrs) return exercise

  switch (raw.type) {
    case 'resistance':
      exercise.bodyweightBase = attrs.bodyweight_base as boolean
      exercise.allowsAddedWeight = attrs.allows_added_weight as boolean
      exercise.bilateral = attrs.bilateral as boolean
      break
    case 'timed_hold':
      exercise.targetDurationSeconds = (attrs.target_duration_seconds as number) ?? null
      break
    case 'distance':
      exercise.distanceUnit = attrs.distance_unit as string
      exercise.tracksElevation = attrs.tracks_elevation as boolean
      break
    case 'interval':
      exercise.defaultWorkSeconds = (attrs.default_work_seconds as number) ?? null
      exercise.defaultRestSeconds = (attrs.default_rest_seconds as number) ?? null
      exercise.defaultRounds = (attrs.default_rounds as number) ?? null
      break
  }

  return exercise
}
