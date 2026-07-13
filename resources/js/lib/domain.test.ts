import { describe, it, expect } from 'vitest'
import type { WorkoutEntry, Workout, EquipmentType } from '@/api/types'
import { entryHasActual, workoutCompletion, equipmentName, groupRoundProgress } from './domain'

describe('entryHasActual', () => {
  it.each([
    [
      'with actualWeight in loadMetric',
      { loadMetric: { actualWeight: 185, targetWeight: null, bodyweightOnly: false } },
      true,
    ],
    [
      'with actualReps in repMetric',
      { repMetric: { actualReps: 10, targetReps: null, toFailure: false, failureRep: null } },
      true,
    ],
    [
      'with both actualWeight and actualReps',
      {
        loadMetric: { actualWeight: 185, targetWeight: null, bodyweightOnly: false },
        repMetric: { actualReps: 10, targetReps: null, toFailure: false, failureRep: null },
      },
      true,
    ],
    [
      'with null actualWeight but actualReps',
      {
        loadMetric: { actualWeight: null, targetWeight: 185, bodyweightOnly: false },
        repMetric: { actualReps: 10, targetReps: null, toFailure: false, failureRep: null },
      },
      true,
    ],
    [
      'with null actualReps but actualWeight',
      {
        loadMetric: { actualWeight: 185, targetWeight: null, bodyweightOnly: false },
        repMetric: { actualReps: null, targetReps: 10, toFailure: false, failureRep: null },
      },
      true,
    ],
    [
      'with both null in loadMetric and repMetric',
      {
        loadMetric: { actualWeight: null, targetWeight: 185, bodyweightOnly: false },
        repMetric: { actualReps: null, targetReps: 10, toFailure: false, failureRep: null },
      },
      false,
    ],
  ])('loadMetric/repMetric: %s', (_, entry, expected) => {
    expect(entryHasActual(entry as WorkoutEntry)).toBe(expected)
  })

  it.each([
    [
      'with actualDurationSeconds',
      { durationMetric: { actualDurationSeconds: 45, targetDurationSeconds: null } },
      true,
    ],
    [
      'with null actualDurationSeconds',
      { durationMetric: { actualDurationSeconds: null, targetDurationSeconds: 60 } },
      false,
    ],
  ])('durationMetric: %s', (_, entry, expected) => {
    expect(entryHasActual(entry as WorkoutEntry)).toBe(expected)
  })

  it.each([
    [
      'with actualDistance',
      {
        distanceMetric: {
          actualDistance: 5.2,
          targetDistance: null,
          lapCount: null,
          strokeCount: null,
        },
      },
      true,
    ],
    [
      'with null actualDistance',
      {
        distanceMetric: {
          actualDistance: null,
          targetDistance: 5,
          lapCount: null,
          strokeCount: null,
        },
      },
      false,
    ],
  ])('distanceMetric: %s', (_, entry, expected) => {
    expect(entryHasActual(entry as WorkoutEntry)).toBe(expected)
  })

  it.each([
    [
      'with completedRounds > 0',
      {
        id: '1',
        workoutId: 'w1',
        exerciseId: 'e1',
        setOrder: 0,
        entryGroupId: null,
        groupRound: null,
        notes: null,
        intervalHeader: {
          programmedRounds: 4,
          completedRounds: 3,
          targetWorkSeconds: 30,
          targetRestSeconds: 15,
          rounds: [],
        },
      },
      true,
    ],
    [
      'with completedRounds = 0',
      {
        id: '2',
        workoutId: 'w2',
        exerciseId: 'e2',
        setOrder: 1,
        entryGroupId: null,
        groupRound: null,
        notes: null,
        intervalHeader: {
          programmedRounds: 4,
          completedRounds: 0,
          targetWorkSeconds: 30,
          targetRestSeconds: 15,
          rounds: [],
        },
      },
      false,
    ],
  ])('intervalHeader: %s', (_, entry, expected) => {
    expect(entryHasActual(entry as WorkoutEntry)).toBe(expected)
  })

  it('returns false when entry has no metrics', () => {
    const entry: WorkoutEntry = {
      id: '1',
      workoutId: 'w1',
      exerciseId: 'e1',
      setOrder: 1,
      entryGroupId: null,
      groupRound: null,
      notes: null,
    }
    expect(entryHasActual(entry)).toBe(false)
  })
})

describe('workoutCompletion', () => {
  it.each([
    ['null workout', null, 0],
    [
      'empty entries array',
      {
        id: 'w1',
        userId: 'u1',
        name: 'Test',
        date: '2026-06-30',
        exhaustion: null,
        soreness: null,
        exercises: [],
        entries: [],
        entryGroups: [],
      },
      0,
    ],
  ])('returns 0 for %s', (_, workout, expected) => {
    expect(workoutCompletion(workout as Workout | null)).toBe(expected)
  })

  it('returns 0 when no entries have actual values', () => {
    const workout: Workout = {
      id: 'w1',
      userId: 'u1',
      name: 'Test',
      date: '2026-06-30',
      exhaustion: null,
      soreness: null,
      exercises: [],
      entries: [
        {
          id: '1',
          workoutId: 'w1',
          exerciseId: 'e1',
          setOrder: 1,
          entryGroupId: null,
          groupRound: null,
          notes: null,
        },
        {
          id: '2',
          workoutId: 'w1',
          exerciseId: 'e1',
          setOrder: 2,
          entryGroupId: null,
          groupRound: null,
          notes: null,
        },
      ],
      entryGroups: [],
    }
    expect(workoutCompletion(workout)).toBe(0)
  })

  it('returns 1 when all entries have actual values', () => {
    const workout: Workout = {
      id: 'w1',
      userId: 'u1',
      name: 'Test',
      date: '2026-06-30',
      exhaustion: null,
      soreness: null,
      exercises: [],
      entries: [
        {
          id: '1',
          workoutId: 'w1',
          exerciseId: 'e1',
          setOrder: 1,
          entryGroupId: null,
          groupRound: null,
          notes: null,
          loadMetric: { actualWeight: 185, targetWeight: null, bodyweightOnly: false },
        },
        {
          id: '2',
          workoutId: 'w1',
          exerciseId: 'e1',
          setOrder: 2,
          entryGroupId: null,
          groupRound: null,
          notes: null,
          loadMetric: { actualWeight: 185, targetWeight: null, bodyweightOnly: false },
        },
      ],
      entryGroups: [],
    }
    expect(workoutCompletion(workout)).toBe(1)
  })

  it('calculates partial completion ratio correctly', () => {
    const workout: Workout = {
      id: 'w1',
      userId: 'u1',
      name: 'Test',
      date: '2026-06-30',
      exhaustion: null,
      soreness: null,
      exercises: [],
      entries: [
        {
          id: '1',
          workoutId: 'w1',
          exerciseId: 'e1',
          setOrder: 1,
          entryGroupId: null,
          groupRound: null,
          notes: null,
          loadMetric: { actualWeight: 185, targetWeight: null, bodyweightOnly: false },
        },
        {
          id: '2',
          workoutId: 'w1',
          exerciseId: 'e1',
          setOrder: 2,
          entryGroupId: null,
          groupRound: null,
          notes: null,
        },
        {
          id: '3',
          workoutId: 'w1',
          exerciseId: 'e1',
          setOrder: 3,
          entryGroupId: null,
          groupRound: null,
          notes: null,
        },
      ],
      entryGroups: [],
    }
    expect(workoutCompletion(workout)).toBe(1 / 3)
  })

  it('handles 2 of 4 entries completed (0.5)', () => {
    const workout: Workout = {
      id: 'w1',
      userId: 'u1',
      name: 'Test',
      date: '2026-06-30',
      exhaustion: null,
      soreness: null,
      exercises: [],
      entries: [
        {
          id: '1',
          workoutId: 'w1',
          exerciseId: 'e1',
          setOrder: 1,
          entryGroupId: null,
          groupRound: null,
          notes: null,
          loadMetric: { actualWeight: 185, targetWeight: null, bodyweightOnly: false },
        },
        {
          id: '2',
          workoutId: 'w1',
          exerciseId: 'e1',
          setOrder: 2,
          entryGroupId: null,
          groupRound: null,
          notes: null,
        },
        {
          id: '3',
          workoutId: 'w1',
          exerciseId: 'e1',
          setOrder: 3,
          entryGroupId: null,
          groupRound: null,
          notes: null,
          repMetric: { actualReps: 8, targetReps: null, toFailure: false, failureRep: null },
        },
        {
          id: '4',
          workoutId: 'w1',
          exerciseId: 'e1',
          setOrder: 4,
          entryGroupId: null,
          groupRound: null,
          notes: null,
        },
      ],
      entryGroups: [],
    }
    expect(workoutCompletion(workout)).toBe(0.5)
  })
})

describe('equipmentName', () => {
  it.each([
    [null, 'Bodyweight'],
    [undefined, 'Bodyweight'],
  ])('returns "Bodyweight" when id is %s', (id, expected) => {
    expect(equipmentName([], (id ?? null) as string | null)).toBe(expected)
  })

  it('returns equipment name when found', () => {
    const equipment: EquipmentType[] = [
      { id: 'eq1', name: 'Barbell', isSystem: true, usageCount: 0 },
      { id: 'eq2', name: 'Dumbbell', isSystem: true, usageCount: 0 },
    ]
    expect(equipmentName(equipment, 'eq1')).toBe('Barbell')
    expect(equipmentName(equipment, 'eq2')).toBe('Dumbbell')
  })

  it('returns empty string when equipment id not found', () => {
    const equipment: EquipmentType[] = [
      { id: 'eq1', name: 'Barbell', isSystem: true, usageCount: 0 },
    ]
    expect(equipmentName(equipment, 'nonexistent')).toBe('')
  })

  it('returns empty string when equipment array is empty and id provided', () => {
    expect(equipmentName([], 'eq1')).toBe('')
  })
})

describe('groupRoundProgress', () => {
  const complete = (round: number) =>
    ({
      groupRound: round,
      loadMetric: { actualWeight: 100, targetWeight: null, bodyweightOnly: false },
    }) as WorkoutEntry

  const incomplete = (round: number) =>
    ({
      groupRound: round,
      loadMetric: { actualWeight: null, targetWeight: 100, bodyweightOnly: false },
    }) as WorkoutEntry

  it('uses plannedRounds when provided', () => {
    const entries: WorkoutEntry[] = [complete(1), incomplete(2)]
    const result = groupRoundProgress(entries, 5)
    expect(result.rounds).toBe(5)
  })

  it('falls back to max groupRound when plannedRounds is undefined', () => {
    const entries: WorkoutEntry[] = [complete(1), complete(2), complete(4)]
    const result = groupRoundProgress(entries, undefined)
    expect(result.rounds).toBe(4)
  })

  it('defaults groupRound to 1 for entries with null groupRound', () => {
    const entries: WorkoutEntry[] = [
      {
        loadMetric: { actualWeight: 100, targetWeight: null, bodyweightOnly: false },
      } as WorkoutEntry,
    ]
    const result = groupRoundProgress(entries, undefined)
    expect(result.rounds).toBe(1)
  })

  it('returns rounds=1 for empty entries with no plannedRounds', () => {
    const entries: WorkoutEntry[] = []
    const result = groupRoundProgress(entries, undefined)
    expect(result.rounds).toBe(1)
  })

  it('identifies completed rounds (partial - rounds 1,3 complete but 2 not)', () => {
    const entries: WorkoutEntry[] = [
      complete(1),
      complete(1),
      incomplete(2),
      incomplete(2),
      complete(3),
      complete(3),
    ]
    const result = groupRoundProgress(entries, 3)
    expect(result.completedRounds).toEqual([1, 3])
  })

  it('identifies all rounds as completed', () => {
    const entries: WorkoutEntry[] = [
      complete(1),
      complete(1),
      complete(2),
      complete(2),
      complete(3),
      complete(3),
    ]
    const result = groupRoundProgress(entries, 3)
    expect(result.completedRounds).toEqual([1, 2, 3])
  })

  it('identifies no rounds as completed', () => {
    const entries: WorkoutEntry[] = [incomplete(1), incomplete(1), incomplete(2), incomplete(2)]
    const result = groupRoundProgress(entries, 2)
    expect(result.completedRounds).toEqual([])
  })

  it('sets displayRound past completed rounds', () => {
    const entries: WorkoutEntry[] = [complete(1), incomplete(2), incomplete(3)]
    const result = groupRoundProgress(entries, 3)
    expect(result.displayRound).toBe(2)
  })

  it('caps displayRound at total when all complete', () => {
    const entries: WorkoutEntry[] = [complete(1), complete(1), complete(2), complete(2)]
    const result = groupRoundProgress(entries, 2)
    expect(result.displayRound).toBe(2)
  })

  it('sets displayRound to 1 when nothing is complete', () => {
    const entries: WorkoutEntry[] = [incomplete(1), incomplete(1), incomplete(2), incomplete(2)]
    const result = groupRoundProgress(entries, 2)
    expect(result.displayRound).toBe(1)
  })

  it('displayRound uses completed count, not position, for non-contiguous completions', () => {
    const entries: WorkoutEntry[] = [complete(1), incomplete(2), complete(3)]
    const result = groupRoundProgress(entries, 3)
    expect(result.completedRounds).toEqual([1, 3])
    expect(result.displayRound).toBe(3)
  })

  it('handles empty array with plannedRounds', () => {
    const result = groupRoundProgress([], 3)
    expect(result.rounds).toBe(3)
    expect(result.completedRounds).toEqual([1, 2, 3])
    expect(result.displayRound).toBe(3)
  })
})
