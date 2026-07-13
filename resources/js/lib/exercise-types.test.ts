import { describe, it, expect } from 'vitest'
import { METRIC_DISPLAY } from './exercise-types'

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
