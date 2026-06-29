import { api } from './client'
import {
  toWorkoutTemplate,
  toWorkoutTemplateListItem,
  toWorkout,
  type RawWorkoutTemplate,
  type RawWorkoutTemplateListItem,
  type RawWorkout,
} from './transformers'
import type { Workout, WorkoutTemplate, WorkoutTemplateListItem } from './types'

export interface CreateTemplatePayload {
  name: string
  notes?: string | null
}

export interface UpdateTemplatePayload {
  name?: string
  notes?: string | null
}

export interface CloneTemplatePayload {
  date: string
  name?: string
}

export async function listTemplates(): Promise<WorkoutTemplateListItem[]> {
  const { data } = await api.get('/templates')
  return (data.data as RawWorkoutTemplateListItem[]).map(toWorkoutTemplateListItem)
}

export async function getTemplate(id: string): Promise<WorkoutTemplate> {
  const { data } = await api.get(`/templates/${id}`)
  return toWorkoutTemplate(data.data as RawWorkoutTemplate)
}

export async function createTemplate(payload: CreateTemplatePayload): Promise<WorkoutTemplate> {
  const { data } = await api.post('/templates', payload)
  return toWorkoutTemplate(data.data as RawWorkoutTemplate)
}

export async function updateTemplate(
  id: string,
  payload: UpdateTemplatePayload
): Promise<WorkoutTemplate> {
  const { data } = await api.put(`/templates/${id}`, payload)
  return toWorkoutTemplate(data.data as RawWorkoutTemplate)
}

export async function deleteTemplate(id: string): Promise<void> {
  await api.delete(`/templates/${id}`)
}

export async function attachExercise(
  templateId: string,
  exerciseId: string
): Promise<WorkoutTemplate> {
  const { data } = await api.post(`/templates/${templateId}/exercises`, {
    exercise_id: Number(exerciseId),
  })
  return toWorkoutTemplate(data.data as RawWorkoutTemplate)
}

export async function detachExercise(templateId: string, exerciseId: string): Promise<void> {
  await api.delete(`/templates/${templateId}/exercises/${exerciseId}`)
}

export async function reorderExercises(
  templateId: string,
  ids: string[]
): Promise<WorkoutTemplate> {
  const { data } = await api.put(`/templates/${templateId}/exercises/reorder`, {
    ids: ids.map(Number),
  })
  return toWorkoutTemplate(data.data as RawWorkoutTemplate)
}

export async function cloneTemplate(
  templateId: string,
  payload: CloneTemplatePayload
): Promise<Workout> {
  const { data } = await api.post(`/templates/${templateId}/clone`, payload)
  return toWorkout(data.data as RawWorkout)
}
