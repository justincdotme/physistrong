import type {
  ExerciseProgressData,
  ExerciseType,
  ProgressMetric,
  ProgressRecord,
  ProgressSeries,
} from './types'
import { EXERCISE_TYPES } from '@/lib/exercise-types'
import { formatDuration } from '@/lib/duration'
import { unitLabel } from '@/lib/units'
import type { MeasurementSystem } from '@/lib/units'

export interface RawDataPoint {
  entry_id: number
  date: string
  value: number
  is_pr: boolean
}

export interface RawVolumePoint {
  workout_id: number
  date: string
  total_volume: number
}

export interface RawMetricSeries {
  metric: ProgressMetric
  has_data: boolean
  data_points: RawDataPoint[]
}

export interface RawProgressResponse {
  exercise_id: number
  exercise_type: ExerciseType
  range: string
  primary_metric: ProgressMetric
  metrics: RawMetricSeries[]
  volume?: RawVolumePoint[]
}

export interface RawRecordEntry {
  value: number | null
  entry_id: number | null
  date: string | null
}

export interface RawRecordsResponse {
  exercise_id: number
  exercise_type: ExerciseType
  records: Record<string, RawRecordEntry>
}

const RECORD_LABELS: Record<string, string> = {
  weight: 'Heaviest',
  reps: 'Most reps',
  volume: 'Top set volume',
  duration: 'Longest time',
  distance: 'Farthest',
  completed_rounds: 'Most rounds',
}

// A plank's best is a hold; a bike ride's is time on the machine.
function recordLabel(key: string, type: ExerciseType): string {
  if (key === 'duration' && type === 'timed_hold') return 'Longest hold'
  return RECORD_LABELS[key] ?? key
}

export function transformProgressData(
  progress: RawProgressResponse,
  records: RawRecordsResponse,
  measurementSystem: MeasurementSystem
): ExerciseProgressData {
  const series: ProgressSeries[] = progress.metrics.map(s => ({
    metric: s.metric,
    hasData: s.has_data,
    points: s.data_points.map(dp => ({
      date: dp.date,
      value: dp.value,
      reps: null,
      entryId: String(dp.entry_id),
      isPR: dp.is_pr,
    })),
  }))

  const volume = (progress.volume ?? []).map(v => ({
    date: v.date,
    value: v.total_volume,
  }))

  const recordsList: ProgressRecord[] = []

  Object.entries(records.records).forEach(([key, record]) => {
    if (record.value === null) return

    let displayValue = String(record.value)
    let unit = ''

    if (key === 'weight' || key === 'volume') {
      unit = unitLabel(measurementSystem, 'weight')
    } else if (key === 'distance') {
      unit = unitLabel(measurementSystem, 'distance')
    } else if (key === 'duration') {
      displayValue = formatDuration(record.value as number)
    }

    recordsList.push({
      label: recordLabel(key, progress.exercise_type),
      value: displayValue,
      unit,
      sub: null,
    })
  })

  return {
    series,
    volume,
    records: recordsList,
    type: progress.exercise_type,
    primaryMetric: progress.primary_metric,
  }
}

export function extractAllTimeBest(
  records: RawRecordsResponse,
  exerciseType: ExerciseType
): number | null {
  const key = EXERCISE_TYPES[exerciseType].recordKey
  const record = records.records[key]
  return record?.value ?? null
}
