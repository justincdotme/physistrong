import { api } from './client'
import {
  toWorkoutListItem,
  toWorkout,
  toWorkoutEntry,
  type RawWorkoutListItem,
  type RawWorkout,
  type RawWorkoutEntry,
} from './transformers'
import type { Workout, WorkoutEntry, WorkoutListItem } from './types'

export interface CreateWorkoutPayload {
  name: string
  date: string
  exhaustion?: number | null
  soreness?: number | null
}

export interface UpdateWorkoutPayload {
  name?: string
  date?: string
  exhaustion?: number | null
  soreness?: number | null
}

export interface CreateEntryPayload {
  exercise_id: number
  set_order: number
  notes?: string | null
  metrics?: Record<string, unknown>
}

export interface UpdateEntryPayload {
  set_order?: number
  notes?: string | null
  metrics?: Record<string, unknown>
}

interface PaginatedResponse {
  items: WorkoutListItem[]
  nextPage: number | null
  total: number
}

export async function listWorkouts(page = 1): Promise<PaginatedResponse> {
  const { data } = await api.get(`/workouts?page=${page}`)
  return {
    items: (data.data as RawWorkoutListItem[]).map(toWorkoutListItem),
    nextPage: data.meta.current_page < data.meta.last_page ? data.meta.current_page + 1 : null,
    total: data.meta.total,
  }
}

export async function getWorkout(id: string): Promise<Workout> {
  const { data } = await api.get(`/workouts/${id}`)
  return toWorkout(data.data as RawWorkout)
}

export async function createWorkout(payload: CreateWorkoutPayload): Promise<Workout> {
  const { data } = await api.post('/workouts', payload)
  return toWorkout(data.data as RawWorkout)
}

export async function updateWorkout(id: string, payload: UpdateWorkoutPayload): Promise<Workout> {
  const { data } = await api.put(`/workouts/${id}`, payload)
  return toWorkout(data.data as RawWorkout)
}

export async function deleteWorkout(id: string): Promise<void> {
  await api.delete(`/workouts/${id}`)
}

export async function attachExercise(workoutId: string, exerciseId: string): Promise<Workout> {
  const { data } = await api.post(`/workouts/${workoutId}/exercises`, {
    exercise_id: Number(exerciseId),
  })
  return toWorkout(data.data as RawWorkout)
}

export async function detachExercise(workoutId: string, exerciseId: string): Promise<void> {
  await api.delete(`/workouts/${workoutId}/exercises/${exerciseId}`)
}

export async function reorderExercises(workoutId: string, ids: string[]): Promise<Workout> {
  const { data } = await api.put(`/workouts/${workoutId}/exercises/reorder`, {
    ids: ids.map(Number),
  })
  return toWorkout(data.data as RawWorkout)
}

export async function createEntry(
  workoutId: string,
  payload: CreateEntryPayload
): Promise<WorkoutEntry> {
  const { data } = await api.post(`/workouts/${workoutId}/entries`, payload)
  return toWorkoutEntry(data.data as RawWorkoutEntry)
}

export async function updateEntry(
  workoutId: string,
  entryId: string,
  payload: UpdateEntryPayload
): Promise<WorkoutEntry> {
  const { data } = await api.put(`/workouts/${workoutId}/entries/${entryId}`, payload)
  return toWorkoutEntry(data.data as RawWorkoutEntry)
}

export async function deleteEntry(workoutId: string, entryId: string): Promise<void> {
  await api.delete(`/workouts/${workoutId}/entries/${entryId}`)
}

export async function reorderEntries(workoutId: string, ids: string[]): Promise<WorkoutEntry[]> {
  const { data } = await api.put(`/workouts/${workoutId}/entries/reorder`, {
    ids: ids.map(Number),
  })
  return (data.data as RawWorkoutEntry[]).map(toWorkoutEntry)
}
