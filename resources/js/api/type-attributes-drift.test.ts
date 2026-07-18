import { describe, it, expect } from 'vitest'
import { toExercise } from './transformers'
import type { RawExercise } from './transformers'
import { EXERCISE_TYPES, buildTypeAttributes } from '@/lib/exercise-types'
import type { ExerciseType } from './types'

const modules = import.meta.glob('../test/mocks/fixtures/exercises/*.json', {
  eager: true,
}) as Record<string, unknown>

function collectRawExercises(): RawExercise[] {
  const out: RawExercise[] = []
  for (const mod of Object.values(modules)) {
    const data = (mod as { default: { data?: unknown } }).default?.data
    const rows = Array.isArray(data) ? data : data ? [data] : []
    for (const row of rows) {
      if (row && typeof row === 'object' && 'type' in row && 'type_attributes' in row) {
        out.push(row as RawExercise)
      }
    }
  }
  return out
}

describe('type_attributes consumer manifest drift', () => {
  const raws = collectRawExercises()

  it('captures at least one exercise fixture to check', () => {
    expect(raws.length).toBeGreaterThan(0)
  })

  it('maps every captured type_attributes key and transforms without throwing', () => {
    for (const raw of raws) {
      expect(() => toExercise(raw)).not.toThrow()
      const type = raw.type as ExerciseType
      const manifest = EXERCISE_TYPES[type]?.attributeKeys ?? []
      for (const key of Object.keys(raw.type_attributes ?? {})) {
        expect(
          manifest.includes(key),
          `Unmapped type_attributes key "${key}" on ${type} exercise "${raw.name}". ` +
            `Map it in toExercise() and add it to EXERCISE_TYPES.${type}.attributeKeys, ` +
            `or add a documented exclusion.`
        ).toBe(true)
      }
    }
  })

  it('buildTypeAttributes produces keys that are in the manifest', () => {
    const forms: Record<ExerciseType, Record<string, unknown>> = {
      resistance: { bodyweight: true, addedWeight: true, bilateral: false },
      timed_hold: { targetDurationSeconds: '60' },
      distance: {},
      interval: { defaultWorkSeconds: '45', defaultRestSeconds: '15', defaultRounds: '8' },
    }

    for (const type of ['resistance', 'timed_hold', 'distance', 'interval'] as ExerciseType[]) {
      const formState = {
        bodyweight: (forms[type].bodyweight as boolean) ?? false,
        addedWeight: (forms[type].addedWeight as boolean) ?? false,
        bilateral: (forms[type].bilateral as boolean) ?? false,
        targetDurationSeconds: (forms[type].targetDurationSeconds as string) ?? '',
        defaultWorkSeconds: (forms[type].defaultWorkSeconds as string) ?? '',
        defaultRestSeconds: (forms[type].defaultRestSeconds as string) ?? '',
        defaultRounds: (forms[type].defaultRounds as string) ?? '',
      }
      const built = buildTypeAttributes(type, formState)
      const manifest = EXERCISE_TYPES[type].attributeKeys

      for (const key of Object.keys(built)) {
        expect(
          manifest.includes(key),
          `buildTypeAttributes produces unmapped key "${key}" for ${type}. ` +
            `Add it to EXERCISE_TYPES.${type}.attributeKeys.`
        ).toBe(true)
      }
    }
  })
})
