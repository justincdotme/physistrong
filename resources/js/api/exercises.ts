import { queryOptions } from '@tanstack/react-query'
import { api } from './client'
import { toExercise, type RawExercise } from './transformers'
import { fetchRecords, fetchProgress } from './progress'
import type { Exercise, ExerciseType } from './types'

export const exerciseQueries = {
  base: ['exercises'] as const,
  list: () => queryOptions({ queryKey: ['exercises'] as const, queryFn: listExercises }),
  detail: (id: string) =>
    queryOptions({
      queryKey: ['exercises', id] as const,
      queryFn: () => getExercise(id),
    }),
  records: (id: string) =>
    queryOptions({
      queryKey: ['exercises', id, 'records'] as const,
      queryFn: () => fetchRecords(id),
    }),
  progress: (id: string, range: string) =>
    queryOptions({
      queryKey: ['exercises', id, 'progress', range] as const,
      queryFn: () => fetchProgress(id, range),
    }),
}

export interface CreateExercisePayload {
  name: string
  type: ExerciseType
  equipment_type_id: number | null
  notes: string | null
  type_attributes: Record<string, unknown>
}

export interface UpdateExercisePayload {
  name: string
  equipment_type_id: number | null
  notes: string | null
  type_attributes?: Record<string, unknown>
}

export async function listExercises(): Promise<Exercise[]> {
  const { data } = await api.get('/exercises')
  return (data.data as RawExercise[]).map(toExercise)
}

export async function getExercise(id: string): Promise<Exercise> {
  const { data } = await api.get(`/exercises/${id}`)
  return toExercise(data.data as RawExercise)
}

export async function createExercise(payload: CreateExercisePayload): Promise<Exercise> {
  const { data } = await api.post('/exercises', payload)
  return toExercise(data.data as RawExercise)
}

export async function updateExercise(
  id: string,
  payload: UpdateExercisePayload
): Promise<Exercise> {
  const { data } = await api.put(`/exercises/${id}`, payload)
  return toExercise(data.data as RawExercise)
}

export async function deleteExercise(id: string): Promise<void> {
  await api.delete(`/exercises/${id}`)
}
