import type { MeasurementSystem } from '@/lib/units'

export interface User {
  id: string
  firstName: string
  lastName: string
  email: string
  measurementSystem: MeasurementSystem
  theme: 'light' | 'dark' | 'system'
}

export interface EquipmentType {
  id: string
  name: string
  isSystem: boolean
  usageCount: number
}

export type ExerciseType = 'resistance' | 'timed_hold' | 'distance' | 'interval'

export interface Exercise {
  id: string
  userId: string | null
  name: string
  type: ExerciseType
  equipmentTypeId: string | null
  notes: string | null
  usageCount: number
  hasLoggedData: boolean
  bodyweightBase?: boolean
  allowsAddedWeight?: boolean
  bilateral?: boolean
  targetDurationSeconds?: number | null
  tracksElevation?: boolean
  defaultWorkSeconds?: number | null
  defaultRestSeconds?: number | null
  defaultRounds?: number | null
}

export interface LoadMetric {
  targetWeight: number | null
  actualWeight: number | null
  bodyweightOnly: boolean
}

export interface RepMetric {
  targetReps: number | null
  actualReps: number | null
  toFailure: boolean
  failureRep: number | null
}

export interface DurationMetric {
  targetDurationSeconds: number | null
  actualDurationSeconds: number | null
}

export interface DistanceMetric {
  targetDistance: number | null
  actualDistance: number | null
  lapCount: number | null
  strokeCount: number | null
}

export interface CardioSettings {
  resistanceLevel: number | null
  incline: number | null
  speed: number | null
  cadence: number | null
}

export interface IntervalRound {
  roundNumber: number
  actualWorkSeconds: number | null
  actualRestSeconds: number | null
  heartRateAvg: number | null
  heartRatePeak: number | null
}

export interface IntervalHeader {
  programmedRounds: number
  completedRounds: number
  targetWorkSeconds: number
  targetRestSeconds: number
  rounds: IntervalRound[]
}

export interface IntensityMetric {
  rpe: number | null
  avgHr: number | null
  maxHr: number | null
}

export interface WorkoutEntry {
  id: string
  workoutId: string
  exerciseId: string
  setOrder: number
  entryGroupId: string | null
  groupRound: number | null
  notes: string | null
  loadMetric?: LoadMetric
  repMetric?: RepMetric
  durationMetric?: DurationMetric
  distanceMetric?: DistanceMetric
  cardioSettings?: CardioSettings
  intervalHeader?: IntervalHeader
  intensityMetric?: IntensityMetric
}

export interface EntryGroup {
  id: string
  workoutId: string
  name: string | null
  plannedRounds: number
  restBetweenExercisesSeconds: number
  restBetweenRoundsSeconds: number | null
}

export interface Workout {
  id: string
  userId: string
  name: string
  date: string
  exhaustion: number | null
  soreness: number | null
  exercises: Array<{ id: string; name: string; type: ExerciseType }>
  entries: WorkoutEntry[]
  entryGroups: EntryGroup[]
}

export interface WorkoutListItem {
  id: string
  name: string
  date: string
  exhaustion: number | null
  soreness: number | null
  exercises: Array<{ id: string; name: string; type: ExerciseType }>
  entriesCount: number
  completedEntriesCount: number
}

export interface TemplateExercise {
  id: string
  exerciseId: string
  name: string
  type: ExerciseType
  equipmentTypeId: string | null
  exerciseOrder: number
  groupId: string | null
}

export interface TemplateEntryGroup {
  id: string
  name: string | null
  plannedRounds: number
  restBetweenExercisesSeconds: number
  restBetweenRoundsSeconds: number | null
  exercises: TemplateExercise[]
}

export interface WorkoutTemplate {
  id: string
  name: string
  notes: string | null
  exercises: TemplateExercise[]
  groups: TemplateEntryGroup[]
}

export interface WorkoutTemplateListItem {
  id: string
  name: string
  notes: string | null
  exercises: Array<{ id: string; name: string; type: ExerciseType }>
}

export interface ProgressPoint {
  date: string
  value: number
  reps: number | null
  entryId: string
  isPR: boolean
  volume?: number
}

export interface ProgressRecord {
  label: string
  value: string
  unit: string
  sub: string | null
}

export interface ExerciseProgressData {
  points: ProgressPoint[]
  volume: { date: string; value: number }[]
  records: ProgressRecord[]
  type: ExerciseType
}

export type TimeRange = '1M' | '3M' | '6M' | '1Y' | 'All'
