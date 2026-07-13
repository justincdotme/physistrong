import { describe, it, expect } from 'vitest'
import { unitLabel, type MeasurementSystem, type MeasurementDimension } from './units'

describe('unitLabel', () => {
  it.each<[MeasurementSystem, MeasurementDimension, string]>([
    ['imperial', 'weight', 'lb'],
    ['imperial', 'distance', 'mi'],
    ['metric', 'weight', 'kg'],
    ['metric', 'distance', 'km'],
  ])('returns %s for system=%s, dimension=%s', (system, dimension, expected) => {
    expect(unitLabel(system, dimension)).toBe(expected)
  })
})
