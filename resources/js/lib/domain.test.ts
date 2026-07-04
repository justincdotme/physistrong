import { describe, it, expect } from 'vitest'
import type { WorkoutEntry, Workout, EquipmentType, Exercise } from '@/api/types'
import {
  entryHasActual,
  workoutCompletion,
  equipmentName,
  exerciseUsageCount,
  equipmentUsageCount,
  bestWeight,
} from './domain'

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

describe('exerciseUsageCount', () => {
  it('returns 0 when exercise not used in any workout', () => {
    const workouts: Workout[] = [
      {
        id: 'w1',
        userId: 'u1',
        name: 'Workout 1',
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
        ],
        entryGroups: [],
      },
    ]
    expect(exerciseUsageCount(workouts, 'e2')).toBe(0)
  })

  it('counts workout containing the exercise', () => {
    const workouts: Workout[] = [
      {
        id: 'w1',
        userId: 'u1',
        name: 'Workout 1',
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
        ],
        entryGroups: [],
      },
    ]
    expect(exerciseUsageCount(workouts, 'e1')).toBe(1)
  })

  it('does not double-count if exercise appears multiple times in one workout', () => {
    const workouts: Workout[] = [
      {
        id: 'w1',
        userId: 'u1',
        name: 'Workout 1',
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
      },
    ]
    expect(exerciseUsageCount(workouts, 'e1')).toBe(1)
  })

  it('counts across multiple workouts', () => {
    const workouts: Workout[] = [
      {
        id: 'w1',
        userId: 'u1',
        name: 'Workout 1',
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
        ],
        entryGroups: [],
      },
      {
        id: 'w2',
        userId: 'u1',
        name: 'Workout 2',
        date: '2026-06-29',
        exhaustion: null,
        soreness: null,
        exercises: [],
        entries: [
          {
            id: '2',
            workoutId: 'w2',
            exerciseId: 'e1',
            setOrder: 1,
            entryGroupId: null,
            groupRound: null,
            notes: null,
          },
        ],
        entryGroups: [],
      },
      {
        id: 'w3',
        userId: 'u1',
        name: 'Workout 3',
        date: '2026-06-28',
        exhaustion: null,
        soreness: null,
        exercises: [],
        entries: [
          {
            id: '3',
            workoutId: 'w3',
            exerciseId: 'e2',
            setOrder: 1,
            entryGroupId: null,
            groupRound: null,
            notes: null,
          },
        ],
        entryGroups: [],
      },
    ]
    expect(exerciseUsageCount(workouts, 'e1')).toBe(2)
    expect(exerciseUsageCount(workouts, 'e2')).toBe(1)
  })
})

describe('equipmentUsageCount', () => {
  it('returns 0 when no exercises use the equipment', () => {
    const exercises: Exercise[] = [
      {
        id: 'e1',
        userId: 'u1',
        name: 'Bench Press',
        type: 'resistance',
        equipmentTypeId: 'eq1',
        notes: null,
        usageCount: 0,
        hasLoggedData: false,
      },
    ]
    expect(equipmentUsageCount(exercises, 'eq2')).toBe(0)
  })

  it('counts exercises using the equipment', () => {
    const exercises: Exercise[] = [
      {
        id: 'e1',
        userId: 'u1',
        name: 'Bench Press',
        type: 'resistance',
        equipmentTypeId: 'eq1',
        notes: null,
        usageCount: 0,
        hasLoggedData: false,
      },
      {
        id: 'e2',
        userId: 'u1',
        name: 'Dumbbell Curl',
        type: 'resistance',
        equipmentTypeId: 'eq2',
        notes: null,
        usageCount: 0,
        hasLoggedData: false,
      },
    ]
    expect(equipmentUsageCount(exercises, 'eq1')).toBe(1)
    expect(equipmentUsageCount(exercises, 'eq2')).toBe(1)
  })

  it('counts multiple exercises using the same equipment', () => {
    const exercises: Exercise[] = [
      {
        id: 'e1',
        userId: 'u1',
        name: 'Dumbbell Curl',
        type: 'resistance',
        equipmentTypeId: 'eq1',
        notes: null,
        usageCount: 0,
        hasLoggedData: false,
      },
      {
        id: 'e2',
        userId: 'u1',
        name: 'Dumbbell Press',
        type: 'resistance',
        equipmentTypeId: 'eq1',
        notes: null,
        usageCount: 0,
        hasLoggedData: false,
      },
      {
        id: 'e3',
        userId: 'u1',
        name: 'Dumbbell Flye',
        type: 'resistance',
        equipmentTypeId: 'eq1',
        notes: null,
        usageCount: 0,
        hasLoggedData: false,
      },
    ]
    expect(equipmentUsageCount(exercises, 'eq1')).toBe(3)
  })

  it('ignores bodyweight exercises with null equipmentTypeId', () => {
    const exercises: Exercise[] = [
      {
        id: 'e1',
        userId: 'u1',
        name: 'Push-up',
        type: 'resistance',
        equipmentTypeId: null,
        notes: null,
        usageCount: 0,
        hasLoggedData: false,
      },
      {
        id: 'e2',
        userId: 'u1',
        name: 'Dumbbell Curl',
        type: 'resistance',
        equipmentTypeId: 'eq1',
        notes: null,
        usageCount: 0,
        hasLoggedData: false,
      },
    ]
    expect(equipmentUsageCount(exercises, 'eq1')).toBe(1)
  })
})

describe('bestWeight', () => {
  it('returns null when no workouts', () => {
    expect(bestWeight([], 'e1')).toBe(null)
  })

  it('returns null when exercise not found in any workout', () => {
    const workouts: Workout[] = [
      {
        id: 'w1',
        userId: 'u1',
        name: 'Workout 1',
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
        ],
        entryGroups: [],
      },
    ]
    expect(bestWeight(workouts, 'e2')).toBe(null)
  })

  it('returns null when exercise found but has no load metric', () => {
    const workouts: Workout[] = [
      {
        id: 'w1',
        userId: 'u1',
        name: 'Workout 1',
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
            durationMetric: { actualDurationSeconds: 60, targetDurationSeconds: null },
          },
        ],
        entryGroups: [],
      },
    ]
    expect(bestWeight(workouts, 'e1')).toBe(null)
  })

  it('returns null when exercise has load metric but no actual weight', () => {
    const workouts: Workout[] = [
      {
        id: 'w1',
        userId: 'u1',
        name: 'Workout 1',
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
            loadMetric: { actualWeight: null, targetWeight: 185, bodyweightOnly: false },
          },
        ],
        entryGroups: [],
      },
    ]
    expect(bestWeight(workouts, 'e1')).toBe(null)
  })

  it('returns the weight from single entry', () => {
    const workouts: Workout[] = [
      {
        id: 'w1',
        userId: 'u1',
        name: 'Workout 1',
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
        ],
        entryGroups: [],
      },
    ]
    expect(bestWeight(workouts, 'e1')).toBe(185)
  })

  it('returns maximum weight from multiple entries in one workout', () => {
    const workouts: Workout[] = [
      {
        id: 'w1',
        userId: 'u1',
        name: 'Workout 1',
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
            loadMetric: { actualWeight: 195, targetWeight: null, bodyweightOnly: false },
          },
          {
            id: '3',
            workoutId: 'w1',
            exerciseId: 'e1',
            setOrder: 3,
            entryGroupId: null,
            groupRound: null,
            notes: null,
            loadMetric: { actualWeight: 190, targetWeight: null, bodyweightOnly: false },
          },
        ],
        entryGroups: [],
      },
    ]
    expect(bestWeight(workouts, 'e1')).toBe(195)
  })

  it('returns maximum weight across multiple workouts', () => {
    const workouts: Workout[] = [
      {
        id: 'w1',
        userId: 'u1',
        name: 'Workout 1',
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
        ],
        entryGroups: [],
      },
      {
        id: 'w2',
        userId: 'u1',
        name: 'Workout 2',
        date: '2026-06-29',
        exhaustion: null,
        soreness: null,
        exercises: [],
        entries: [
          {
            id: '2',
            workoutId: 'w2',
            exerciseId: 'e1',
            setOrder: 1,
            entryGroupId: null,
            groupRound: null,
            notes: null,
            loadMetric: { actualWeight: 205, targetWeight: null, bodyweightOnly: false },
          },
        ],
        entryGroups: [],
      },
      {
        id: 'w3',
        userId: 'u1',
        name: 'Workout 3',
        date: '2026-06-28',
        exhaustion: null,
        soreness: null,
        exercises: [],
        entries: [
          {
            id: '3',
            workoutId: 'w3',
            exerciseId: 'e1',
            setOrder: 1,
            entryGroupId: null,
            groupRound: null,
            notes: null,
            loadMetric: { actualWeight: 195, targetWeight: null, bodyweightOnly: false },
          },
        ],
        entryGroups: [],
      },
    ]
    expect(bestWeight(workouts, 'e1')).toBe(205)
  })

  it('ignores entries for different exercises', () => {
    const workouts: Workout[] = [
      {
        id: 'w1',
        userId: 'u1',
        name: 'Workout 1',
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
            exerciseId: 'e2',
            setOrder: 2,
            entryGroupId: null,
            groupRound: null,
            notes: null,
            loadMetric: { actualWeight: 95, targetWeight: null, bodyweightOnly: false },
          },
        ],
        entryGroups: [],
      },
    ]
    expect(bestWeight(workouts, 'e1')).toBe(185)
    expect(bestWeight(workouts, 'e2')).toBe(95)
  })

  it('handles decimal weights', () => {
    const workouts: Workout[] = [
      {
        id: 'w1',
        userId: 'u1',
        name: 'Workout 1',
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
            loadMetric: { actualWeight: 185.5, targetWeight: null, bodyweightOnly: false },
          },
          {
            id: '2',
            workoutId: 'w1',
            exerciseId: 'e1',
            setOrder: 2,
            entryGroupId: null,
            groupRound: null,
            notes: null,
            loadMetric: { actualWeight: 185.75, targetWeight: null, bodyweightOnly: false },
          },
        ],
        entryGroups: [],
      },
    ]
    expect(bestWeight(workouts, 'e1')).toBe(185.75)
  })
})
