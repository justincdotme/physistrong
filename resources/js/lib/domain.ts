import type { Exercise, EquipmentType, Workout, WorkoutEntry, ExerciseType } from '@/api/types'

export const TYPE_LABELS: Record<ExerciseType, string> = {
  resistance: 'Resistance',
  timed_hold: 'Timed Hold',
  distance: 'Distance / Time',
  interval: 'Interval',
}

export function entryHasActual(e: WorkoutEntry): boolean {
  if (e.loadMetric || e.repMetric) {
    const w = e.loadMetric ? e.loadMetric.actualWeight : null
    const r = e.repMetric ? e.repMetric.actualReps : null
    return w != null || r != null
  }
  if (e.durationMetric && e.durationMetric.actualDurationSeconds != null) return true
  if (e.distanceMetric && e.distanceMetric.actualDistance != null) return true
  if (e.intervalHeader && e.intervalHeader.completedRounds > 0) return true
  return false
}

export function workoutCompletion(workout: Workout | null): number {
  if (!workout || !workout.entries.length) return 0
  const done = workout.entries.filter(entryHasActual).length
  return done / workout.entries.length
}

export function exerciseById(exercises: Exercise[], id: string): Exercise | undefined {
  return exercises.find(e => e.id === id)
}

export function equipmentName(equipment: EquipmentType[], id: string | null): string {
  if (!id) return 'Bodyweight'
  const eq = equipment.find(e => e.id === id)
  return eq ? eq.name : ''
}

export function exerciseUsageCount(workouts: Workout[], exerciseId: string): number {
  return workouts.filter(w => w.entries.some(e => e.exerciseId === exerciseId)).length
}

export function equipmentUsageCount(exercises: Exercise[], equipmentId: string): number {
  return exercises.filter(e => e.equipmentTypeId === equipmentId).length
}

export function bestWeight(workouts: Workout[], exerciseId: string): number | null {
  let best = -Infinity
  workouts.forEach(w =>
    w.entries.forEach(e => {
      if (e.exerciseId === exerciseId && e.loadMetric && e.loadMetric.actualWeight != null) {
        best = Math.max(best, e.loadMetric.actualWeight)
      }
    })
  )
  return best === -Infinity ? null : best
}
