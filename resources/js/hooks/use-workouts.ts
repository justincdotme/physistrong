import { useApp } from '@/lib/use-app'
import type { Workout } from '@/api/types'

interface UseWorkoutsResult {
  data: Workout[]
  loading: boolean
  error: null
}

export function useWorkouts(): UseWorkoutsResult {
  const { workouts } = useApp()
  const sorted = [...workouts].sort((a, b) => (a.date < b.date ? 1 : -1))
  return { data: sorted, loading: false, error: null }
}

interface UseWorkoutResult {
  data: Workout | null
  loading: boolean
  error: null
}

export function useWorkout(id: string): UseWorkoutResult {
  const { workouts } = useApp()
  return { data: workouts.find(w => w.id === id) ?? null, loading: false, error: null }
}
