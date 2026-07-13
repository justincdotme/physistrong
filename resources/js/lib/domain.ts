import type { Exercise, EquipmentType, Workout, WorkoutEntry } from '@/api/types'

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

export function groupRoundProgress(
  entries: WorkoutEntry[],
  plannedRounds: number | undefined
): { rounds: number; completedRounds: number[]; displayRound: number } {
  const rounds = plannedRounds || Math.max(1, ...entries.map(e => e.groupRound || 1))
  const completedRounds: number[] = []
  for (let r = 1; r <= rounds; r++) {
    if (entries.filter(e => e.groupRound === r).every(e => entryHasActual(e))) {
      completedRounds.push(r)
    }
  }
  const c = completedRounds.length
  const displayRound = Math.min(c + (c < rounds ? 1 : 0), rounds)
  return { rounds, completedRounds, displayRound }
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
