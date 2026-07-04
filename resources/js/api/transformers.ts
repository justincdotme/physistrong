import type {
  User,
  EquipmentType,
  Exercise,
  ExerciseType,
  WorkoutListItem,
  Workout,
  WorkoutEntry,
  WorkoutTemplate,
  WorkoutTemplateListItem,
  TemplateExercise,
} from '@/api/types'
import type { MeasurementSystem } from '@/lib/units'

export interface RawUser {
  id: number
  email: string
  first_name: string | null
  last_name: string | null
  measurement_system: MeasurementSystem
  theme: 'light' | 'dark' | 'system'
}

export interface RawEquipmentType {
  id: number
  name: string
  is_system: boolean
  usage_count: number
}

export function toUser(raw: RawUser): User {
  return {
    id: String(raw.id),
    firstName: raw.first_name ?? '',
    lastName: raw.last_name ?? '',
    email: raw.email,
    measurementSystem: raw.measurement_system,
    theme: raw.theme,
  }
}

export function toEquipmentType(raw: RawEquipmentType): EquipmentType {
  return {
    id: String(raw.id),
    name: raw.name,
    isSystem: raw.is_system,
    usageCount: raw.usage_count ?? 0,
  }
}

export interface RawExercise {
  id: number
  name: string
  type: string
  notes: string | null
  user_id: number | null
  equipment_type_id: number | null
  usage_count: number
  has_logged_data: boolean
  type_attributes: Record<string, unknown> | null
  created_at: string
  updated_at: string
}

export function toExercise(raw: RawExercise): Exercise {
  const exercise: Exercise = {
    id: String(raw.id),
    userId: raw.user_id !== null ? String(raw.user_id) : null,
    name: raw.name,
    type: raw.type as ExerciseType,
    equipmentTypeId: raw.equipment_type_id !== null ? String(raw.equipment_type_id) : null,
    notes: raw.notes,
    usageCount: raw.usage_count ?? 0,
    hasLoggedData: raw.has_logged_data ?? false,
  }

  const attrs = raw.type_attributes
  if (!attrs) return exercise

  switch (raw.type) {
    case 'resistance':
      exercise.bodyweightBase = attrs.bodyweight_base as boolean
      exercise.allowsAddedWeight = attrs.allows_added_weight as boolean
      exercise.bilateral = attrs.bilateral as boolean
      break
    case 'timed_hold':
      exercise.targetDurationSeconds = (attrs.target_duration_seconds as number) ?? null
      break
    case 'distance':
      exercise.tracksElevation = attrs.tracks_elevation as boolean
      break
    case 'interval':
      exercise.defaultWorkSeconds = (attrs.default_work_seconds as number) ?? null
      exercise.defaultRestSeconds = (attrs.default_rest_seconds as number) ?? null
      exercise.defaultRounds = (attrs.default_rounds as number) ?? null
      break
  }

  return exercise
}

export interface RawWorkoutListItem {
  id: number
  name: string
  date: string
  exhaustion: number | null
  soreness: number | null
  exercises: Array<{
    id: number
    name: string
    type: string
    equipment_type_id: number | null
    exercise_order: number
  }>
  entries_count: number
  completed_entries_count: number
  created_at: string
  updated_at: string
}

export interface RawWorkoutExercise {
  id: number
  name: string
  type: string
  equipment_type_id: number | null
  exercise_order: number
}

export interface RawWorkoutEntry {
  id: number
  workout_id: number
  exercise_id: number
  set_order: number
  entry_group_id: number | null
  group_round: number | null
  notes: string | null
  exercise?: { id: number; name: string; type: string; equipment_type_id: number | null }
  metrics: Record<string, Record<string, unknown>>
  created_at: string
  updated_at: string
}

export interface RawEntryGroup {
  id: number
  name: string | null
  planned_rounds: number
  rest_between_exercises_seconds: number
  rest_between_rounds_seconds: number | null
}

export interface RawWorkout {
  id: number
  name: string
  date: string
  exhaustion: number | null
  soreness: number | null
  exercises: RawWorkoutExercise[]
  entries: RawWorkoutEntry[]
  groups: RawEntryGroup[]
  created_at: string
  updated_at: string
}

export function toWorkoutListItem(raw: RawWorkoutListItem): WorkoutListItem {
  return {
    id: String(raw.id),
    name: raw.name,
    date: raw.date,
    exhaustion: raw.exhaustion,
    soreness: raw.soreness,
    exercises: raw.exercises.map(ex => ({
      id: String(ex.id),
      name: ex.name,
      type: ex.type as ExerciseType,
    })),
    entriesCount: raw.entries_count,
    completedEntriesCount: raw.completed_entries_count,
  }
}

export function toWorkout(raw: RawWorkout): Workout {
  return {
    id: String(raw.id),
    userId: '',
    name: raw.name,
    date: raw.date,
    exhaustion: raw.exhaustion,
    soreness: raw.soreness,
    exercises: raw.exercises.map(ex => ({
      id: String(ex.id),
      name: ex.name,
      type: ex.type as ExerciseType,
    })),
    entries: raw.entries.map(toWorkoutEntry),
    entryGroups: (raw.groups ?? []).map(g => ({
      id: String(g.id),
      workoutId: String(raw.id),
      name: g.name,
      plannedRounds: g.planned_rounds,
      restBetweenExercisesSeconds: g.rest_between_exercises_seconds,
      restBetweenRoundsSeconds: g.rest_between_rounds_seconds,
    })),
  }
}

export function toWorkoutEntry(raw: RawWorkoutEntry): WorkoutEntry {
  const entry: WorkoutEntry = {
    id: String(raw.id),
    workoutId: String(raw.workout_id),
    exerciseId: String(raw.exercise_id),
    setOrder: raw.set_order,
    entryGroupId: raw.entry_group_id !== null ? String(raw.entry_group_id) : null,
    groupRound: raw.group_round,
    notes: raw.notes,
  }

  const metrics = raw.metrics

  if (metrics.load) {
    entry.loadMetric = {
      targetWeight: metrics.load.target_weight as number | null,
      actualWeight: metrics.load.actual_weight as number | null,
      bodyweightOnly: metrics.load.bodyweight_only as boolean,
    }
  }

  if (metrics.reps) {
    entry.repMetric = {
      targetReps: metrics.reps.target_reps as number | null,
      actualReps: metrics.reps.actual_reps as number | null,
      toFailure: metrics.reps.to_failure as boolean,
      failureRep: metrics.reps.failure_rep as number | null,
    }
  }

  if (metrics.duration) {
    entry.durationMetric = {
      targetDurationSeconds: metrics.duration.target_duration_seconds as number | null,
      actualDurationSeconds: metrics.duration.actual_duration_seconds as number | null,
    }
  }

  if (metrics.distance) {
    entry.distanceMetric = {
      targetDistance: metrics.distance.target_distance as number | null,
      actualDistance: metrics.distance.actual_distance as number | null,
      lapCount: metrics.distance.lap_count as number | null,
      strokeCount: metrics.distance.stroke_count as number | null,
    }
  }

  if (metrics.cardio_settings) {
    entry.cardioSettings = {
      resistanceLevel: metrics.cardio_settings.resistance_level as number | null,
      incline: metrics.cardio_settings.incline as number | null,
      speed: metrics.cardio_settings.speed as number | null,
      cadence: metrics.cardio_settings.cadence as number | null,
    }
  }

  if (metrics.interval_header) {
    entry.intervalHeader = {
      programmedRounds: metrics.interval_header.programmed_rounds as number,
      completedRounds: metrics.interval_header.completed_rounds as number,
      targetWorkSeconds: metrics.interval_header.target_work_seconds as number,
      targetRestSeconds: metrics.interval_header.target_rest_seconds as number,
      rounds: (metrics.interval_header.rounds as Array<Record<string, unknown>>).map(round => ({
        roundNumber: round.round_number as number,
        actualWorkSeconds: round.actual_work_seconds as number | null,
        actualRestSeconds: round.actual_rest_seconds as number | null,
        heartRateAvg: round.heart_rate_avg as number | null,
        heartRatePeak: round.heart_rate_peak as number | null,
      })),
    }
  }

  if (metrics.intensity) {
    entry.intensityMetric = {
      rpe: metrics.intensity.rpe as number | null,
      avgHr: metrics.intensity.avg_hr as number | null,
      maxHr: metrics.intensity.max_hr as number | null,
    }
  }

  return entry
}

export interface RawTemplateExercise {
  id: number
  name: string
  type: string
  equipment_type_id: number | null
  exercise_order: number
  template_entry_group_id: number | null
}

export interface RawTemplateGroup {
  id: number
  name: string | null
  planned_rounds: number
  rest_between_exercises_seconds: number
  rest_between_rounds_seconds: number | null
}

export interface RawWorkoutTemplate {
  id: number
  name: string
  notes: string | null
  exercises: RawTemplateExercise[]
  groups: RawTemplateGroup[]
  created_at: string
  updated_at: string
}

export interface RawWorkoutTemplateListItem {
  id: number
  name: string
  notes: string | null
  exercises: Array<{
    id: number
    name: string
    type: string
    equipment_type_id: number | null
    exercise_order: number
    template_entry_group_id: number | null
  }>
  created_at: string
  updated_at: string
}

function toTemplateExercise(raw: RawTemplateExercise): TemplateExercise {
  return {
    id: String(raw.id),
    exerciseId: String(raw.id),
    name: raw.name,
    type: raw.type as ExerciseType,
    equipmentTypeId: raw.equipment_type_id !== null ? String(raw.equipment_type_id) : null,
    exerciseOrder: raw.exercise_order,
    groupId: raw.template_entry_group_id !== null ? String(raw.template_entry_group_id) : null,
  }
}

export function toWorkoutTemplate(raw: RawWorkoutTemplate): WorkoutTemplate {
  const allExercises = raw.exercises.map(toTemplateExercise)
  const ungrouped = allExercises.filter(e => e.groupId === null)
  const groups = raw.groups.map(g => {
    const groupId = String(g.id)
    return {
      id: groupId,
      name: g.name,
      plannedRounds: g.planned_rounds,
      restBetweenExercisesSeconds: g.rest_between_exercises_seconds,
      restBetweenRoundsSeconds: g.rest_between_rounds_seconds,
      exercises: allExercises.filter(e => e.groupId === groupId),
    }
  })

  return {
    id: String(raw.id),
    name: raw.name,
    notes: raw.notes,
    exercises: ungrouped,
    groups,
  }
}

export function toWorkoutTemplateListItem(
  raw: RawWorkoutTemplateListItem
): WorkoutTemplateListItem {
  return {
    id: String(raw.id),
    name: raw.name,
    notes: raw.notes,
    exercises: raw.exercises.map(ex => ({
      id: String(ex.id),
      name: ex.name,
      type: ex.type as ExerciseType,
    })),
  }
}

export function toMetricsPayload(entry: WorkoutEntry): Record<string, unknown> {
  const payload: Record<string, unknown> = {}

  if (entry.loadMetric) {
    payload.load = {
      target_weight: entry.loadMetric.targetWeight,
      actual_weight: entry.loadMetric.actualWeight,
      bodyweight_only: entry.loadMetric.bodyweightOnly,
    }
  }

  if (entry.repMetric) {
    payload.reps = {
      target_reps: entry.repMetric.targetReps,
      actual_reps: entry.repMetric.actualReps,
      to_failure: entry.repMetric.toFailure,
      failure_rep: entry.repMetric.failureRep,
    }
  }

  if (entry.durationMetric) {
    payload.duration = {
      target_duration_seconds: entry.durationMetric.targetDurationSeconds,
      actual_duration_seconds: entry.durationMetric.actualDurationSeconds,
    }
  }

  if (entry.distanceMetric) {
    payload.distance = {
      target_distance: entry.distanceMetric.targetDistance,
      actual_distance: entry.distanceMetric.actualDistance,
      lap_count: entry.distanceMetric.lapCount,
      stroke_count: entry.distanceMetric.strokeCount,
    }
  }

  if (entry.cardioSettings) {
    payload.cardio_settings = {
      resistance_level: entry.cardioSettings.resistanceLevel,
      incline: entry.cardioSettings.incline,
      speed: entry.cardioSettings.speed,
      cadence: entry.cardioSettings.cadence,
    }
  }

  if (entry.intervalHeader) {
    payload.interval_header = {
      programmed_rounds: entry.intervalHeader.programmedRounds,
      completed_rounds: entry.intervalHeader.completedRounds,
      target_work_seconds: entry.intervalHeader.targetWorkSeconds,
      target_rest_seconds: entry.intervalHeader.targetRestSeconds,
      rounds: entry.intervalHeader.rounds.map(round => ({
        round_number: round.roundNumber,
        actual_work_seconds: round.actualWorkSeconds,
        actual_rest_seconds: round.actualRestSeconds,
        heart_rate_avg: round.heartRateAvg,
        heart_rate_peak: round.heartRatePeak,
      })),
    }
  }

  if (entry.intensityMetric) {
    payload.intensity = {
      rpe: entry.intensityMetric.rpe,
      avg_hr: entry.intensityMetric.avgHr,
      max_hr: entry.intensityMetric.maxHr,
    }
  }

  return payload
}
