import { describe, it, expect } from 'vitest'
import { METRIC_DISPLAY, buildTypeAttributes } from './exercise-types'
import type { ExerciseType } from '@/api/types'

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

interface CreateFormState {
  type: ExerciseType
  bodyweight: boolean
  addedWeight: boolean
  bilateral: boolean
  targetDurationSeconds: string
  defaultWorkSeconds: string
  defaultRestSeconds: string
  defaultRounds: string
}

describe('buildTypeAttributes', () => {
  it('builds resistance attributes from form state', () => {
    const form: CreateFormState = {
      type: 'resistance',
      bodyweight: true,
      addedWeight: false,
      bilateral: true,
      targetDurationSeconds: '',
      defaultWorkSeconds: '',
      defaultRestSeconds: '',
      defaultRounds: '',
    }
    const result = buildTypeAttributes('resistance', form)
    expect(result).toEqual({
      bodyweight_base: true,
      allows_added_weight: false,
      bilateral: true,
    })
  })

  it('builds timed_hold attributes with target duration', () => {
    const form: CreateFormState = {
      type: 'timed_hold',
      bodyweight: false,
      addedWeight: true,
      bilateral: true,
      targetDurationSeconds: '90',
      defaultWorkSeconds: '',
      defaultRestSeconds: '',
      defaultRounds: '',
    }
    const result = buildTypeAttributes('timed_hold', form)
    expect(result).toEqual({ target_duration_seconds: 90 })
  })

  it('builds timed_hold attributes without target duration when empty', () => {
    const form: CreateFormState = {
      type: 'timed_hold',
      bodyweight: false,
      addedWeight: true,
      bilateral: true,
      targetDurationSeconds: '',
      defaultWorkSeconds: '',
      defaultRestSeconds: '',
      defaultRounds: '',
    }
    const result = buildTypeAttributes('timed_hold', form)
    expect(result).toEqual({})
  })

  it('builds distance attributes as empty object', () => {
    const form: CreateFormState = {
      type: 'distance',
      bodyweight: false,
      addedWeight: true,
      bilateral: true,
      targetDurationSeconds: '',
      defaultWorkSeconds: '',
      defaultRestSeconds: '',
      defaultRounds: '',
    }
    const result = buildTypeAttributes('distance', form)
    expect(result).toEqual({})
  })

  it('builds interval attributes with all fields', () => {
    const form: CreateFormState = {
      type: 'interval',
      bodyweight: false,
      addedWeight: true,
      bilateral: true,
      targetDurationSeconds: '',
      defaultWorkSeconds: '45',
      defaultRestSeconds: '15',
      defaultRounds: '10',
    }
    const result = buildTypeAttributes('interval', form)
    expect(result).toEqual({
      default_work_seconds: 45,
      default_rest_seconds: 15,
      default_rounds: 10,
    })
  })

  it('builds interval attributes with partial fields', () => {
    const form: CreateFormState = {
      type: 'interval',
      bodyweight: false,
      addedWeight: true,
      bilateral: true,
      targetDurationSeconds: '',
      defaultWorkSeconds: '30',
      defaultRestSeconds: '',
      defaultRounds: '',
    }
    const result = buildTypeAttributes('interval', form)
    expect(result).toEqual({ default_work_seconds: 30 })
  })
})
