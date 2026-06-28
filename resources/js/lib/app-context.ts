import { createContext } from 'react'
import type { User, EquipmentType, Exercise, Workout, WorkoutTemplate } from '@/api/types'

interface Toast {
  id: string
  message: string
}

export interface AppContextValue {
  user: User
  authed: boolean
  exercises: Exercise[]
  equipment: EquipmentType[]
  workouts: Workout[]
  templates: WorkoutTemplate[]
  toasts: Toast[]
  uid: (prefix: string) => string
  toast: (message: string) => void
  updateUser: (patch: Partial<User>) => void
  login: () => void
  logout: () => void
  addExercise: (ex: Partial<Exercise> & { name: string; type: Exercise['type'] }) => Exercise
  updateExercise: (id: string, patch: Partial<Exercise>) => void
  deleteExercise: (id: string) => void
  addEquipment: (name: string) => EquipmentType
  deleteEquipment: (id: string) => void
  addWorkout: (w: Partial<Workout> & { name: string; date: string }) => Workout
  updateWorkout: (id: string, patch: Partial<Workout>) => void
  deleteWorkout: (id: string) => void
  createFromTemplate: (templateId: string, date: string, name?: string) => Workout | null
  updateTemplate: (id: string, patch: Partial<WorkoutTemplate>) => void
  deleteTemplate: (id: string) => void
}

export const AppContext = createContext<AppContextValue | null>(null)
