import type { Exercise, ExerciseType, PrimaryMetric } from '@/api/types'
import type { CreateEntryPayload } from '@/api/workouts'
import type { MeasurementSystem } from '@/lib/units'
import { unitLabel } from '@/lib/units'
import { formatDuration } from '@/lib/duration'
import { assertNever } from '@/lib/utils'

type EntryMetrics = CreateEntryPayload['metrics']

export interface ExerciseTypeConfig {
  label: string
  defaultEntryMetrics: (ex: Exercise) => EntryMetrics
  recordKey: string
  attributeKeys: readonly string[]
  attributeRows: (ex: Exercise) => Array<[string, string]>
}

export const EXERCISE_TYPES: Record<ExerciseType, ExerciseTypeConfig> = {
  resistance: {
    label: 'Resistance',
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

interface TypeAttributesFormState {
  bodyweight: boolean
  addedWeight: boolean
  bilateral: boolean
  targetDurationSeconds: number | null
  defaultWorkSeconds: number | null
  defaultRestSeconds: number | null
  defaultRounds: number | null
}

export function buildTypeAttributes(
  type: ExerciseType,
  form: TypeAttributesFormState
): Record<string, unknown> {
  const attrs: Record<string, unknown> = {}

  switch (type) {
    case 'resistance':
      attrs.bodyweight_base = form.bodyweight
      attrs.allows_added_weight = form.addedWeight
      attrs.bilateral = form.bilateral
      break
    case 'timed_hold':
      if (form.targetDurationSeconds != null) {
        attrs.target_duration_seconds = form.targetDurationSeconds
      }
      break
    case 'distance':
      break
    case 'interval':
      if (form.defaultWorkSeconds != null) {
        attrs.default_work_seconds = form.defaultWorkSeconds
      }
      if (form.defaultRestSeconds != null) {
        attrs.default_rest_seconds = form.defaultRestSeconds
      }
      if (form.defaultRounds != null) {
        attrs.default_rounds = form.defaultRounds
      }
      break
    default:
      assertNever(type)
  }

  return attrs
}
