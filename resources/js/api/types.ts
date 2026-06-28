export interface User {
  id: string
  firstName: string
  lastName: string
  email: string
  weightUnit: 'kg' | 'lb'
  distanceUnit: 'miles' | 'kilometers'
  theme: 'light' | 'dark' | 'system'
}

export interface EquipmentType {
  id: string
  name: string
  isSystem: boolean
}

export type ExerciseType = 'resistance' | 'timed_hold' | 'distance' | 'interval'

export interface Exercise {
  id: string
  userId: string
  name: string
  type: ExerciseType
  equipmentTypeId: string | null
  notes: string | null
  bodyweightBase?: boolean
  allowsAddedWeight?: boolean
  bilateral?: boolean
  tracksElevation?: boolean
  defaultWorkSeconds?: number
  defaultRestSeconds?: number
  defaultRounds?: number
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
  distanceUnit: string
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
  entries: WorkoutEntry[]
  entryGroups: EntryGroup[]
}

export interface TemplateExercise {
  id: string
  exerciseId: string
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
  userId: string
  name: string
  notes: string | null
  exercises: TemplateExercise[]
  groups: TemplateEntryGroup[]
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
