import { useApp } from '@/lib/store'
import type { Exercise } from '@/api/types'

interface UseExercisesResult {
  data: Exercise[]
  loading: boolean
  error: null
}

export function useExercises(): UseExercisesResult {
  const { exercises } = useApp()
  return { data: exercises, loading: false, error: null }
}

interface UseExerciseResult {
  data: Exercise | null
  loading: boolean
  error: null
}

export function useExercise(id: string): UseExerciseResult {
  const { exercises } = useApp()
  return { data: exercises.find(e => e.id === id) ?? null, loading: false, error: null }
}
