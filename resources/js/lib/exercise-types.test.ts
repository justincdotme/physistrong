import { describe, it, expect } from 'vitest'
import { EXERCISE_TYPES, METRIC_DISPLAY } from './exercise-types'
import type { Exercise, ExerciseType } from '@/api/types'

const sample = (type: ExerciseType): Exercise => ({
  id: '1',
  userId: null,
  name: 'sample',
  type,
  equipmentTypeId: null,
  notes: null,
  usageCount: 0,
  hasLoggedData: false,
})

describe('EXERCISE_TYPES defaultEntryMetrics stays within allowedMetrics', () => {
  it.each(Object.keys(EXERCISE_TYPES) as ExerciseType[])(
    'default payload keys for %s stay within the allowed metrics the backend accepts',
    type => {
      const cfg = EXERCISE_TYPES[type]
      const allowed = new Set<string>([...cfg.metrics.required, ...cfg.metrics.optional])
      const metrics = cfg.defaultEntryMetrics(sample(type)) as Record<string, unknown>
      for (const key of Object.keys(metrics)) {
        expect(allowed.has(key), `${type} default emits disallowed metric "${key}"`).toBe(true)
      }
    }
  )
})

describe('METRIC_DISPLAY', () => {
  it('labels reps (not weight) when the API resolves primary_metric to reps', () => {
    expect(METRIC_DISPLAY.reps.unit('imperial')).toBe('reps')
    expect(METRIC_DISPLAY.reps.yLabel('imperial')).toContain('reps')
    expect(METRIC_DISPLAY.reps.yLabel('imperial')).not.toMatch(/lb|kg/)
  })

  it('labels weight for weighted resistance', () => {
    expect(METRIC_DISPLAY.weight.unit('imperial')).toBe('lb')
    expect(METRIC_DISPLAY.weight.unit('metric')).toBe('kg')
  })
})
