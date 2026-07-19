import { queryOptions, infiniteQueryOptions } from '@tanstack/react-query'
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

/**
 * Cache-update strategy per mutation: prefer writing the server response
 * into cache when the endpoint returns the full resource. Use optimistic
 * writes that reconcile on success for latency-sensitive updates. Fall
 * back to invalidation for void-response operations, but flush pending
 * debounced entry updates first so the refetch does not overwrite
 * optimistic state. Anything that changes list membership invalidates
 * the base ['workouts'] key.
 */
export const workoutQueries = {
  base: ['workouts'] as const,
  lists: ['workouts', 'list'] as const,
  list: () =>
    infiniteQueryOptions({
      queryKey: ['workouts', 'list'] as const,
      queryFn: ({ pageParam }) => listWorkouts(pageParam),
      getNextPageParam: (last: PaginatedResponse) => last.nextPage,
      initialPageParam: 1,
    }),
  detail: (id: string) =>
    queryOptions({
      queryKey: ['workouts', id] as const,
      queryFn: () => getWorkout(id),
    }),
  picker: () =>
    queryOptions({
      queryKey: ['workouts', 'list', 'picker'] as const,
      queryFn: () => listWorkouts(1),
    }),
}

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

export interface CreateGroupPayload {
  name?: string | null
  planned_rounds: number
  rest_between_exercises_seconds: number
  rest_between_rounds_seconds: number | null
}

export interface AssignEntryPayload {
  entry_id: number
  group_round: number
}

export interface CopyWorkoutPayload {
  date: string
  name?: string
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

export async function createGroup(
  workoutId: string,
  payload: CreateGroupPayload
): Promise<Workout> {
  const { data } = await api.post(`/workouts/${workoutId}/groups`, payload)
  return toWorkout(data.data as RawWorkout)
}

export async function deleteGroup(workoutId: string, groupId: string): Promise<void> {
  await api.delete(`/workouts/${workoutId}/groups/${groupId}`)
}

export async function assignEntries(
  workoutId: string,
  groupId: string,
  entries: AssignEntryPayload[]
): Promise<Workout> {
  const { data } = await api.post(`/workouts/${workoutId}/groups/${groupId}/entries`, { entries })
  return toWorkout(data.data as RawWorkout)
}

export async function copyWorkout(
  workoutId: string,
  payload: CopyWorkoutPayload
): Promise<Workout> {
  const { data } = await api.post(`/workouts/${workoutId}/copy`, payload)
  return toWorkout(data.data as RawWorkout)
}
