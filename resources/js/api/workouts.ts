import { api } from './client'
import { toWorkoutListItem, type RawWorkoutListItem } from './transformers'
import type { Workout, WorkoutListItem } from './types'

interface WorkoutListResponse {
  items: WorkoutListItem[]
  nextPage: number | undefined
  total: number
}

interface PaginationMeta {
  current_page: number
  last_page: number
  total: number
}

export async function listWorkouts(page: number = 1): Promise<WorkoutListResponse> {
  const { data } = await api.get('/workouts', { params: { page } })
  const meta = data.meta as PaginationMeta
  const items = (data.data as RawWorkoutListItem[]).map(toWorkoutListItem)
  const nextPage = meta.current_page < meta.last_page ? meta.current_page + 1 : undefined

  return {
    items,
    nextPage,
    total: meta.total,
  }
}

interface CreateWorkoutPayload {
  name: string
  date: string
  exhaustion?: number
  soreness?: number
}

export async function createWorkout(payload: CreateWorkoutPayload): Promise<Workout> {
  const { data } = await api.post('/workouts', payload)
  return data.data as Workout
}
