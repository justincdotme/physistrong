import type { Exercise, ExerciseType, PrimaryMetric } from '@/api/types'
import type { CreateEntryPayload } from '@/api/workouts'
import type { MeasurementSystem } from '@/lib/units'
import { unitLabel } from '@/lib/units'
import { formatDuration } from '@/lib/formatters'

// Metric identifiers as named in the backend metric enum.
export type MetricDimension =
  'load' | 'reps' | 'duration' | 'distance' | 'cardio_settings' | 'intensity' | 'interval_header'

type EntryMetrics = CreateEntryPayload['metrics']

export interface ExerciseTypeConfig {
  label: string
  metrics: { required: MetricDimension[]; optional: MetricDimension[] }
  defaultEntryMetrics: (ex: Exercise) => EntryMetrics
  recordKey: string
  attributeKeys: readonly string[]
  attributeRows: (ex: Exercise) => Array<[string, string]>
}

export const EXERCISE_TYPES: Record<ExerciseType, ExerciseTypeConfig> = {
  resistance: {
    label: 'Resistance',
    metrics: { required: ['load', 'reps'], optional: ['intensity'] },
    defaultEntryMetrics: ex => ({
      load: { target_weight: null, actual_weight: null, bodyweight_only: !!ex.bodyweightBase },
      reps: { target_reps: null, actual_reps: null, to_failure: false, failure_rep: null },
    }),
    recordKey: 'reps',
    attributeKeys: ['bodyweight_base', 'allows_added_weight', 'bilateral'],
    attributeRows: ex => [
      ['Bodyweight base', ex.bodyweightBase ? 'Yes' : 'No'],
      ['Allows added weight', ex.allowsAddedWeight ? 'Yes' : 'No'],
      ['Bilateral', ex.bilateral ? 'Yes' : 'No (single-arm/leg)'],
    ],
  },
  timed_hold: {
    label: 'Timed Hold',
    metrics: { required: ['duration'], optional: ['load', 'intensity'] },
    defaultEntryMetrics: () => ({
      duration: { target_duration_seconds: null, actual_duration_seconds: null },
    }),
    recordKey: 'duration',
    attributeKeys: ['target_duration_seconds'],
    attributeRows: ex =>
      ex.targetDurationSeconds != null
        ? [['Target duration', formatDuration(ex.targetDurationSeconds)]]
        : [],
  },
  distance: {
    label: 'Distance / Time',
    metrics: { required: ['distance'], optional: ['duration', 'cardio_settings', 'intensity'] },
    defaultEntryMetrics: () => ({
      distance: {
        target_distance: null,
        actual_distance: null,
        lap_count: null,
        stroke_count: null,
      },
      duration: { target_duration_seconds: null, actual_duration_seconds: null },
    }),
    recordKey: 'distance',
    attributeKeys: ['tracks_elevation'],
    attributeRows: ex => [['Tracks elevation', ex.tracksElevation ? 'Yes' : 'No']],
  },
  interval: {
    label: 'Interval',
    metrics: {
      required: ['interval_header'],
      optional: ['cardio_settings', 'distance', 'intensity'],
    },
    defaultEntryMetrics: ex => {
      const n = ex.defaultRounds || 8
      return {
        interval_header: {
          programmed_rounds: n,
          completed_rounds: 0,
          target_work_seconds: ex.defaultWorkSeconds || 60,
          target_rest_seconds: ex.defaultRestSeconds || 60,
          rounds: Array.from({ length: n }, (_, k) => ({
            round_number: k + 1,
            actual_work_seconds: null,
            actual_rest_seconds: null,
            heart_rate_avg: null,
            heart_rate_peak: null,
          })),
        },
      }
    },
    recordKey: 'completed_rounds',
    attributeKeys: ['default_work_seconds', 'default_rest_seconds', 'default_rounds'],
    attributeRows: ex => [
      ['Default work', formatDuration(ex.defaultWorkSeconds)],
      ['Default rest', formatDuration(ex.defaultRestSeconds)],
      ['Default rounds', String(ex.defaultRounds || '—')],
    ],
  },
}

// Filter/picker options in canonical order. Consumers needing an "all"
// pseudo-option prepend it themselves.
export const TYPE_OPTIONS: Array<{ value: ExerciseType; label: string }> = (
  ['resistance', 'timed_hold', 'distance', 'interval'] as const
).map(t => ({ value: t, label: EXERCISE_TYPES[t].label }))

// Keyed by the API's per-exercise primary_metric rather than by exercise type,
// so bodyweight-only lifts plot reps while weighted lifts plot weight.
export const METRIC_DISPLAY: Record<
  PrimaryMetric,
  {
    yLabel: (system: MeasurementSystem) => string
    unit: (system: MeasurementSystem) => string
    valueFormatter?: (v: number) => string
  }
> = {
  weight: { yLabel: s => `Top set (${unitLabel(s, 'weight')})`, unit: s => unitLabel(s, 'weight') },
  reps: { yLabel: () => 'Top set (reps)', unit: () => 'reps' },
  duration: {
    yLabel: () => 'Duration',
    unit: () => '',
    valueFormatter: v => formatDuration(Math.round(v)),
  },
  distance: {
    yLabel: s => `Distance (${unitLabel(s, 'distance')})`,
    unit: s => unitLabel(s, 'distance'),
  },
  completed_rounds: { yLabel: () => 'Rounds', unit: () => 'rounds' },
}
