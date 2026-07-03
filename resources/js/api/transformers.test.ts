import { describe, it, expect } from 'vitest'
import {
  toUser,
  toEquipmentType,
  toExercise,
  toWorkoutListItem,
  toWorkout,
  toWorkoutEntry,
  toWorkoutTemplate,
  toWorkoutTemplateListItem,
  toMetricsPayload,
  type RawUser,
  type RawEquipmentType,
  type RawExercise,
  type RawWorkoutListItem,
  type RawWorkout,
  type RawWorkoutEntry,
  type RawWorkoutTemplate,
  type RawWorkoutTemplateListItem,
} from './transformers'
import type { WorkoutEntry } from '@/api/types'

describe('toUser', () => {
  it('maps RawUser fields to User with camelCase and id as string', () => {
    const raw: RawUser = {
      id: 3,
      email: 'test@example.com',
      first_name: 'Claude',
      last_name: 'Ai',
      measurement_system: 'imperial',
      theme: 'system',
    }

    const result = toUser(raw)

    expect(result).toEqual({
      id: '3',
      firstName: 'Claude',
      lastName: 'Ai',
      email: 'test@example.com',
      measurementSystem: 'imperial',
      theme: 'system',
    })
  })

  it('handles null first_name and last_name with empty string defaults', () => {
    const raw: RawUser = {
      id: 5,
      email: 'user@example.com',
      first_name: null,
      last_name: null,
      measurement_system: 'metric',
      theme: 'light',
    }

    const result = toUser(raw)

    expect(result.firstName).toBe('')
    expect(result.lastName).toBe('')
  })
})

describe('toEquipmentType', () => {
  const systemEquipment: RawEquipmentType = {
    id: 1,
    name: 'barbell',
    is_system: true,
    usage_count: 179,
  }

  const userEquipment: RawEquipmentType = {
    id: 19,
    name: 'Capture Test Equipment',
    is_system: false,
    usage_count: 0,
  }

  it('maps RawEquipmentType fields to EquipmentType with camelCase', () => {
    const result = toEquipmentType(systemEquipment)

    expect(result).toEqual({
      id: '1',
      name: 'barbell',
      isSystem: true,
      usageCount: 179,
    })
  })

  it('handles user-created equipment', () => {
    const result = toEquipmentType(userEquipment)

    expect(result).toEqual({
      id: '19',
      name: 'Capture Test Equipment',
      isSystem: false,
      usageCount: 0,
    })
  })
})

describe('toExercise', () => {
  describe('resistance exercise type attributes', () => {
    it('maps resistance exercise with type_attributes', () => {
      const raw: RawExercise = {
        id: 895,
        name: 'Capture Test Resistance 1782864880612',
        type: 'resistance',
        notes: null,
        user_id: 3,
        equipment_type_id: 25,
        usage_count: 0,
        has_logged_data: false,
        type_attributes: {
          bodyweight_base: true,
          allows_added_weight: true,
          bilateral: false,
        },
        created_at: '2026-01-01T00:00:00.000000Z',
        updated_at: '2026-01-01T00:00:00.000000Z',
      }

      const result = toExercise(raw)

      expect(result).toMatchObject({
        id: '895',
        name: 'Capture Test Resistance 1782864880612',
        type: 'resistance',
        notes: null,
        userId: '3',
        equipmentTypeId: '25',
        usageCount: 0,
        hasLoggedData: false,
        bodyweightBase: true,
        allowsAddedWeight: true,
        bilateral: false,
      })
      expect(result.targetDurationSeconds).toBeUndefined()
      expect(result.defaultWorkSeconds).toBeUndefined()
    })
  })

  describe('timed_hold exercise type attributes', () => {
    it('maps timed_hold exercise with type_attributes', () => {
      const raw: RawExercise = {
        id: 896,
        name: 'Capture Test Hold 1782864880612',
        type: 'timed_hold',
        notes: null,
        user_id: 3,
        equipment_type_id: 25,
        usage_count: 0,
        has_logged_data: false,
        type_attributes: {
          target_duration_seconds: 60,
        },
        created_at: '2026-01-01T00:00:00.000000Z',
        updated_at: '2026-01-01T00:00:00.000000Z',
      }

      const result = toExercise(raw)

      expect(result).toMatchObject({
        id: '896',
        type: 'timed_hold',
        targetDurationSeconds: 60,
      })
      expect(result.bodyweightBase).toBeUndefined()
      expect(result.defaultWorkSeconds).toBeUndefined()
    })
  })

  describe('distance exercise type attributes', () => {
    it('maps distance exercise with type_attributes', () => {
      const raw: RawExercise = {
        id: 897,
        name: 'Capture Test Distance 1782864880612',
        type: 'distance',
        notes: null,
        user_id: 3,
        equipment_type_id: 25,
        usage_count: 0,
        has_logged_data: false,
        type_attributes: {
          tracks_elevation: true,
        },
        created_at: '2026-01-01T00:00:00.000000Z',
        updated_at: '2026-01-01T00:00:00.000000Z',
      }

      const result = toExercise(raw)

      expect(result).toMatchObject({
        id: '897',
        type: 'distance',
        tracksElevation: true,
      })
      expect(result.bodyweightBase).toBeUndefined()
      expect(result.targetDurationSeconds).toBeUndefined()
      expect(result.defaultWorkSeconds).toBeUndefined()
    })
  })

  describe('interval exercise type attributes', () => {
    it('maps interval exercise with type_attributes', () => {
      const raw: RawExercise = {
        id: 898,
        name: 'Capture Test Interval 1782864880612',
        type: 'interval',
        notes: null,
        user_id: 3,
        equipment_type_id: 25,
        usage_count: 0,
        has_logged_data: false,
        type_attributes: {
          default_work_seconds: 30,
          default_rest_seconds: 20,
          default_rounds: 4,
        },
        created_at: '2026-01-01T00:00:00.000000Z',
        updated_at: '2026-01-01T00:00:00.000000Z',
      }

      const result = toExercise(raw)

      expect(result).toMatchObject({
        id: '898',
        type: 'interval',
        defaultWorkSeconds: 30,
        defaultRestSeconds: 20,
        defaultRounds: 4,
      })
      expect(result.bodyweightBase).toBeUndefined()
      expect(result.targetDurationSeconds).toBeUndefined()
    })
  })

  describe('exercises without type_attributes', () => {
    it('skips type attributes when type_attributes is null', () => {
      const raw: RawExercise = {
        id: 899,
        name: 'Exercise without attributes',
        type: 'resistance',
        notes: 'some notes',
        user_id: 3,
        equipment_type_id: 25,
        usage_count: 5,
        has_logged_data: true,
        type_attributes: null,
        created_at: '2026-01-01T00:00:00.000000Z',
        updated_at: '2026-01-01T00:00:00.000000Z',
      }

      const result = toExercise(raw)

      expect(result).toMatchObject({
        id: '899',
        name: 'Exercise without attributes',
        type: 'resistance',
        notes: 'some notes',
        usageCount: 5,
        hasLoggedData: true,
      })
      expect(result.bodyweightBase).toBeUndefined()
      expect(result.allowsAddedWeight).toBeUndefined()
      expect(result.bilateral).toBeUndefined()
    })
  })

  describe('exercises with system scope', () => {
    it('handles exercises with null user_id and equipment_type_id', () => {
      const raw: RawExercise = {
        id: 900,
        name: 'System Exercise',
        type: 'resistance',
        notes: null,
        user_id: null,
        equipment_type_id: null,
        usage_count: 50,
        has_logged_data: true,
        type_attributes: null,
        created_at: '2026-01-01T00:00:00.000000Z',
        updated_at: '2026-01-01T00:00:00.000000Z',
      }

      const result = toExercise(raw)

      expect(result.userId).toBeNull()
      expect(result.equipmentTypeId).toBeNull()
    })
  })
})

describe('toWorkoutListItem', () => {
  it('maps RawWorkoutListItem fields to WorkoutListItem with camelCase', () => {
    const raw: RawWorkoutListItem = {
      id: 29,
      name: 'Capture Test Workout',
      date: '2026-06-16',
      exhaustion: null,
      soreness: null,
      exercises: [
        {
          id: 906,
          name: 'Capture Test Show Bench',
          type: 'resistance',
          equipment_type_id: null,
          exercise_order: 0,
        },
        {
          id: 907,
          name: 'Capture Test Show Intervals',
          type: 'interval',
          equipment_type_id: null,
          exercise_order: 1,
        },
      ],
      entries_count: 4,
      completed_entries_count: 0,
      created_at: '2026-01-01T00:00:00.000000Z',
      updated_at: '2026-01-01T00:00:00.000000Z',
    }

    const result = toWorkoutListItem(raw)

    expect(result).toEqual({
      id: '29',
      name: 'Capture Test Workout',
      date: '2026-06-16',
      exhaustion: null,
      soreness: null,
      exercises: [
        { id: '906', name: 'Capture Test Show Bench', type: 'resistance' },
        { id: '907', name: 'Capture Test Show Intervals', type: 'interval' },
      ],
      entriesCount: 4,
      completedEntriesCount: 0,
    })
  })

  it('maps empty exercises array', () => {
    const raw: RawWorkoutListItem = {
      id: 2,
      name: 'Empty Workout',
      date: '2026-06-15',
      exhaustion: 3,
      soreness: 2,
      exercises: [],
      entries_count: 0,
      completed_entries_count: 0,
      created_at: '2026-01-01T00:00:00.000000Z',
      updated_at: '2026-01-01T00:00:00.000000Z',
    }

    const result = toWorkoutListItem(raw)

    expect(result.exercises).toEqual([])
    expect(result.entriesCount).toBe(0)
    expect(result.completedEntriesCount).toBe(0)
  })
})

describe('toWorkoutEntry', () => {
  describe('load metric', () => {
    it('maps load metric with numeric actual_weight from string', () => {
      const raw: RawWorkoutEntry = {
        id: 41,
        workout_id: 43,
        exercise_id: 906,
        set_order: 0,
        entry_group_id: 4,
        group_round: 1,
        notes: null,
        metrics: {
          load: {
            target_weight: '135.00',
            actual_weight: '130.00',
            bodyweight_only: false,
          },
        },
        created_at: '2026-01-01T00:00:00.000000Z',
        updated_at: '2026-01-01T00:00:00.000000Z',
      }

      const result = toWorkoutEntry(raw)

      expect(result.loadMetric).toEqual({
        targetWeight: '135.00' as unknown as number,
        actualWeight: '130.00' as unknown as number,
        bodyweightOnly: false,
      })
      expect(result.repMetric).toBeUndefined()
    })
  })

  describe('reps metric', () => {
    it('maps reps metric with all fields', () => {
      const raw: RawWorkoutEntry = {
        id: 41,
        workout_id: 43,
        exercise_id: 906,
        set_order: 0,
        entry_group_id: null,
        group_round: null,
        notes: null,
        metrics: {
          reps: {
            target_reps: 8,
            actual_reps: 8,
            to_failure: false,
            failure_rep: null,
          },
        },
        created_at: '2026-01-01T00:00:00.000000Z',
        updated_at: '2026-01-01T00:00:00.000000Z',
      }

      const result = toWorkoutEntry(raw)

      expect(result.repMetric).toEqual({
        targetReps: 8,
        actualReps: 8,
        toFailure: false,
        failureRep: null,
      })
    })
  })

  describe('duration metric', () => {
    it('maps duration metric', () => {
      const raw: RawWorkoutEntry = {
        id: 43,
        workout_id: 43,
        exercise_id: 906,
        set_order: 2,
        entry_group_id: null,
        group_round: null,
        notes: null,
        metrics: {
          duration: {
            target_duration_seconds: 120,
            actual_duration_seconds: 115,
          },
        },
        created_at: '2026-01-01T00:00:00.000000Z',
        updated_at: '2026-01-01T00:00:00.000000Z',
      }

      const result = toWorkoutEntry(raw)

      expect(result.durationMetric).toEqual({
        targetDurationSeconds: 120,
        actualDurationSeconds: 115,
      })
    })
  })

  describe('distance metric', () => {
    it('maps distance metric with all fields', () => {
      const raw: RawWorkoutEntry = {
        id: 50,
        workout_id: 43,
        exercise_id: 901,
        set_order: 0,
        entry_group_id: null,
        group_round: null,
        notes: null,
        metrics: {
          distance: {
            target_distance: 5.0,
            actual_distance: 4.8,
            lap_count: 10,
            stroke_count: 1200,
          },
        },
        created_at: '2026-01-01T00:00:00.000000Z',
        updated_at: '2026-01-01T00:00:00.000000Z',
      }

      const result = toWorkoutEntry(raw)

      expect(result.distanceMetric).toEqual({
        targetDistance: 5.0,
        actualDistance: 4.8,
        lapCount: 10,
        strokeCount: 1200,
      })
    })
  })

  describe('cardio settings metric', () => {
    it('maps cardio_settings metric', () => {
      const raw: RawWorkoutEntry = {
        id: 51,
        workout_id: 43,
        exercise_id: 902,
        set_order: 0,
        entry_group_id: null,
        group_round: null,
        notes: null,
        metrics: {
          cardio_settings: {
            resistance_level: 5,
            incline: 2.5,
            speed: 8.0,
            cadence: 160,
          },
        },
        created_at: '2026-01-01T00:00:00.000000Z',
        updated_at: '2026-01-01T00:00:00.000000Z',
      }

      const result = toWorkoutEntry(raw)

      expect(result.cardioSettings).toEqual({
        resistanceLevel: 5,
        incline: 2.5,
        speed: 8.0,
        cadence: 160,
      })
    })
  })

  describe('interval header metric with rounds', () => {
    it('maps interval_header metric with nested rounds array', () => {
      const raw: RawWorkoutEntry = {
        id: 42,
        workout_id: 43,
        exercise_id: 907,
        set_order: 1,
        entry_group_id: null,
        group_round: null,
        notes: null,
        metrics: {
          interval_header: {
            programmed_rounds: 4,
            completed_rounds: 4,
            target_work_seconds: 30,
            target_rest_seconds: 15,
            rounds: [
              {
                round_number: 1,
                actual_work_seconds: 30,
                actual_rest_seconds: 15,
                heart_rate_avg: null,
                heart_rate_peak: null,
              },
              {
                round_number: 2,
                actual_work_seconds: 31,
                actual_rest_seconds: 14,
                heart_rate_avg: 140,
                heart_rate_peak: 152,
              },
              {
                round_number: 3,
                actual_work_seconds: 30,
                actual_rest_seconds: 15,
                heart_rate_avg: null,
                heart_rate_peak: null,
              },
              {
                round_number: 4,
                actual_work_seconds: 29,
                actual_rest_seconds: 16,
                heart_rate_avg: null,
                heart_rate_peak: null,
              },
            ],
          },
        },
        created_at: '2026-01-01T00:00:00.000000Z',
        updated_at: '2026-01-01T00:00:00.000000Z',
      }

      const result = toWorkoutEntry(raw)

      expect(result.intervalHeader).toBeDefined()
      expect(result.intervalHeader?.programmedRounds).toBe(4)
      expect(result.intervalHeader?.completedRounds).toBe(4)
      expect(result.intervalHeader?.targetWorkSeconds).toBe(30)
      expect(result.intervalHeader?.targetRestSeconds).toBe(15)
      expect(result.intervalHeader?.rounds).toHaveLength(4)
      expect(result.intervalHeader?.rounds[0]).toEqual({
        roundNumber: 1,
        actualWorkSeconds: 30,
        actualRestSeconds: 15,
        heartRateAvg: null,
        heartRatePeak: null,
      })
      expect(result.intervalHeader?.rounds[1]).toEqual({
        roundNumber: 2,
        actualWorkSeconds: 31,
        actualRestSeconds: 14,
        heartRateAvg: 140,
        heartRatePeak: 152,
      })
    })
  })

  describe('intensity metric', () => {
    it('maps intensity metric', () => {
      const raw: RawWorkoutEntry = {
        id: 52,
        workout_id: 43,
        exercise_id: 903,
        set_order: 0,
        entry_group_id: null,
        group_round: null,
        notes: 'felt good',
        metrics: {
          intensity: {
            rpe: 8,
            avg_hr: 145,
            max_hr: 165,
          },
        },
        created_at: '2026-01-01T00:00:00.000000Z',
        updated_at: '2026-01-01T00:00:00.000000Z',
      }

      const result = toWorkoutEntry(raw)

      expect(result.intensityMetric).toEqual({
        rpe: 8,
        avgHr: 145,
        maxHr: 165,
      })
    })
  })

  describe('entry group mapping', () => {
    it('converts entry_group_id and group_round to camelCase', () => {
      const raw: RawWorkoutEntry = {
        id: 41,
        workout_id: 43,
        exercise_id: 906,
        set_order: 0,
        entry_group_id: 4,
        group_round: 1,
        notes: null,
        metrics: {},
        created_at: '2026-01-01T00:00:00.000000Z',
        updated_at: '2026-01-01T00:00:00.000000Z',
      }

      const result = toWorkoutEntry(raw)

      expect(result.entryGroupId).toBe('4')
      expect(result.groupRound).toBe(1)
    })

    it('handles null entry_group_id and group_round', () => {
      const raw: RawWorkoutEntry = {
        id: 42,
        workout_id: 43,
        exercise_id: 907,
        set_order: 1,
        entry_group_id: null,
        group_round: null,
        notes: null,
        metrics: {},
        created_at: '2026-01-01T00:00:00.000000Z',
        updated_at: '2026-01-01T00:00:00.000000Z',
      }

      const result = toWorkoutEntry(raw)

      expect(result.entryGroupId).toBeNull()
      expect(result.groupRound).toBeNull()
    })
  })

  describe('empty metrics', () => {
    it('omits metric properties when metrics object is empty', () => {
      const raw: RawWorkoutEntry = {
        id: 53,
        workout_id: 43,
        exercise_id: 904,
        set_order: 0,
        entry_group_id: null,
        group_round: null,
        notes: null,
        metrics: {},
        created_at: '2026-01-01T00:00:00.000000Z',
        updated_at: '2026-01-01T00:00:00.000000Z',
      }

      const result = toWorkoutEntry(raw)

      expect(result.loadMetric).toBeUndefined()
      expect(result.repMetric).toBeUndefined()
      expect(result.durationMetric).toBeUndefined()
      expect(result.distanceMetric).toBeUndefined()
      expect(result.cardioSettings).toBeUndefined()
      expect(result.intervalHeader).toBeUndefined()
      expect(result.intensityMetric).toBeUndefined()
    })
  })
})

describe('toWorkout', () => {
  it('maps RawWorkout with entries and groups, delegating entries to toWorkoutEntry', () => {
    const raw: RawWorkout = {
      id: 43,
      name: 'Capture Test Show Workout',
      date: '2026-06-01',
      exhaustion: null,
      soreness: null,
      exercises: [
        {
          id: 906,
          name: 'Capture Test Show Bench',
          type: 'resistance',
          equipment_type_id: null,
          exercise_order: 0,
        },
        {
          id: 907,
          name: 'Capture Test Show Intervals',
          type: 'interval',
          equipment_type_id: null,
          exercise_order: 1,
        },
      ],
      groups: [
        {
          id: 4,
          name: 'Superset A',
          planned_rounds: 3,
          rest_between_exercises_seconds: 30,
          rest_between_rounds_seconds: 90,
        },
      ],
      entries: [
        {
          id: 41,
          workout_id: 43,
          exercise_id: 906,
          set_order: 0,
          entry_group_id: 4,
          group_round: 1,
          notes: null,
          metrics: {
            load: {
              target_weight: '135.00',
              actual_weight: '130.00',
              bodyweight_only: false,
            },
            reps: {
              target_reps: 8,
              actual_reps: 8,
              to_failure: false,
              failure_rep: null,
            },
          },
          created_at: '2026-01-01T00:00:00.000000Z',
          updated_at: '2026-01-01T00:00:00.000000Z',
        },
        {
          id: 42,
          workout_id: 43,
          exercise_id: 907,
          set_order: 1,
          entry_group_id: null,
          group_round: null,
          notes: null,
          metrics: {
            interval_header: {
              programmed_rounds: 4,
              completed_rounds: 4,
              target_work_seconds: 30,
              target_rest_seconds: 15,
              rounds: [
                {
                  round_number: 1,
                  actual_work_seconds: 30,
                  actual_rest_seconds: 15,
                  heart_rate_avg: null,
                  heart_rate_peak: null,
                },
                {
                  round_number: 2,
                  actual_work_seconds: 31,
                  actual_rest_seconds: 14,
                  heart_rate_avg: null,
                  heart_rate_peak: null,
                },
                {
                  round_number: 3,
                  actual_work_seconds: 30,
                  actual_rest_seconds: 15,
                  heart_rate_avg: null,
                  heart_rate_peak: null,
                },
                {
                  round_number: 4,
                  actual_work_seconds: 29,
                  actual_rest_seconds: 16,
                  heart_rate_avg: null,
                  heart_rate_peak: null,
                },
              ],
            },
          },
          created_at: '2026-01-01T00:00:00.000000Z',
          updated_at: '2026-01-01T00:00:00.000000Z',
        },
      ],
      created_at: '2026-01-01T00:00:00.000000Z',
      updated_at: '2026-01-01T00:00:00.000000Z',
    }

    const result = toWorkout(raw)

    expect(result).toMatchObject({
      id: '43',
      userId: '',
      name: 'Capture Test Show Workout',
      date: '2026-06-01',
      exhaustion: null,
      soreness: null,
    })
    expect(result.entries).toHaveLength(2)
    const [entry0, entry1] = result.entries
    expect(entry0).toBeDefined()
    expect(entry1).toBeDefined()
    expect(entry0?.id).toBe('41')
    expect(entry0?.workoutId).toBe('43')
    expect(entry0?.exerciseId).toBe('906')
    expect(entry0?.setOrder).toBe(0)
    expect(entry0?.entryGroupId).toBe('4')
    expect(entry0?.groupRound).toBe(1)
    expect(entry0?.loadMetric).toBeDefined()
    expect(entry0?.repMetric).toBeDefined()
    expect(entry1?.intervalHeader).toBeDefined()
    expect(entry1?.intervalHeader?.rounds).toHaveLength(4)
  })

  describe('entry groups mapping', () => {
    it('maps entryGroups array with snake_case to camelCase conversion', () => {
      const raw: RawWorkout = {
        id: 43,
        name: 'Test Workout',
        date: '2026-06-01',
        exhaustion: null,
        soreness: null,
        exercises: [],
        entries: [],
        groups: [
          {
            id: 4,
            name: 'Superset A',
            planned_rounds: 3,
            rest_between_exercises_seconds: 30,
            rest_between_rounds_seconds: 90,
          },
          {
            id: 5,
            name: null,
            planned_rounds: 5,
            rest_between_exercises_seconds: 45,
            rest_between_rounds_seconds: null,
          },
        ],
        created_at: '2026-01-01T00:00:00.000000Z',
        updated_at: '2026-01-01T00:00:00.000000Z',
      }

      const result = toWorkout(raw)

      expect(result.entryGroups).toHaveLength(2)
      expect(result.entryGroups[0]).toEqual({
        id: '4',
        workoutId: '43',
        name: 'Superset A',
        plannedRounds: 3,
        restBetweenExercisesSeconds: 30,
        restBetweenRoundsSeconds: 90,
      })
      expect(result.entryGroups[1]).toEqual({
        id: '5',
        workoutId: '43',
        name: null,
        plannedRounds: 5,
        restBetweenExercisesSeconds: 45,
        restBetweenRoundsSeconds: null,
      })
    })

    it('handles empty groups array', () => {
      const raw: RawWorkout = {
        id: 43,
        name: 'Test Workout',
        date: '2026-06-01',
        exhaustion: null,
        soreness: null,
        exercises: [],
        entries: [],
        groups: [],
        created_at: '2026-01-01T00:00:00.000000Z',
        updated_at: '2026-01-01T00:00:00.000000Z',
      }

      const result = toWorkout(raw)

      expect(result.entryGroups).toEqual([])
    })
  })
})

describe('toWorkoutTemplate', () => {
  it('maps RawWorkoutTemplate with empty exercises and groups', () => {
    const raw: RawWorkoutTemplate = {
      id: 2,
      name: 'Capture Test Template 1782864881129',
      notes: null,
      exercises: [],
      groups: [],
      created_at: '2026-01-01T00:00:00.000000Z',
      updated_at: '2026-01-01T00:00:00.000000Z',
    }

    const result = toWorkoutTemplate(raw)

    expect(result).toEqual({
      id: '2',
      name: 'Capture Test Template 1782864881129',
      notes: null,
      exercises: [],
      groups: [],
    })
  })

  it('maps template exercises with group assignments', () => {
    const raw: RawWorkoutTemplate = {
      id: 3,
      name: 'Complex Template',
      notes: 'A template with groups',
      exercises: [
        {
          id: 10,
          name: 'Exercise A',
          type: 'resistance',
          equipment_type_id: 1,
          exercise_order: 0,
          template_entry_group_id: null,
        },
        {
          id: 11,
          name: 'Exercise B',
          type: 'resistance',
          equipment_type_id: 2,
          exercise_order: 1,
          template_entry_group_id: 5,
        },
        {
          id: 12,
          name: 'Exercise C',
          type: 'distance',
          equipment_type_id: null,
          exercise_order: 2,
          template_entry_group_id: 5,
        },
        {
          id: 13,
          name: 'Exercise D',
          type: 'interval',
          equipment_type_id: null,
          exercise_order: 3,
          template_entry_group_id: null,
        },
      ],
      groups: [
        {
          id: 5,
          name: 'Superset',
          planned_rounds: 3,
          rest_between_exercises_seconds: 30,
          rest_between_rounds_seconds: 60,
        },
      ],
      created_at: '2026-01-01T00:00:00.000000Z',
      updated_at: '2026-01-01T00:00:00.000000Z',
    }

    const result = toWorkoutTemplate(raw)

    expect(result.exercises).toHaveLength(2)
    expect(result.exercises).toContainEqual({
      id: '10',
      exerciseId: '10',
      name: 'Exercise A',
      type: 'resistance',
      equipmentTypeId: '1',
      exerciseOrder: 0,
      groupId: null,
    })
    expect(result.exercises).toContainEqual({
      id: '13',
      exerciseId: '13',
      name: 'Exercise D',
      type: 'interval',
      equipmentTypeId: null,
      exerciseOrder: 3,
      groupId: null,
    })

    expect(result.groups).toHaveLength(1)
    const [group0] = result.groups
    expect(group0).toBeDefined()
    expect(group0?.id).toBe('5')
    expect(group0?.name).toBe('Superset')
    expect(group0?.plannedRounds).toBe(3)
    expect(group0?.restBetweenExercisesSeconds).toBe(30)
    expect(group0?.restBetweenRoundsSeconds).toBe(60)
    expect(group0?.exercises).toHaveLength(2)
    expect(group0?.exercises).toContainEqual({
      id: '11',
      exerciseId: '11',
      name: 'Exercise B',
      type: 'resistance',
      equipmentTypeId: '2',
      exerciseOrder: 1,
      groupId: '5',
    })
    expect(group0?.exercises).toContainEqual({
      id: '12',
      exerciseId: '12',
      name: 'Exercise C',
      type: 'distance',
      equipmentTypeId: null,
      exerciseOrder: 2,
      groupId: '5',
    })
  })
})

describe('toWorkoutTemplateListItem', () => {
  it('maps RawWorkoutTemplateListItem with exercises', () => {
    const raw: RawWorkoutTemplateListItem = {
      id: 1,
      name: 'Template 1',
      notes: 'Notes here',
      exercises: [
        {
          id: 10,
          name: 'Bench Press',
          type: 'resistance',
          equipment_type_id: null,
          exercise_order: 0,
          template_entry_group_id: null,
        },
        {
          id: 11,
          name: 'Squats',
          type: 'resistance',
          equipment_type_id: null,
          exercise_order: 1,
          template_entry_group_id: null,
        },
      ],
      created_at: '2026-01-01T00:00:00.000000Z',
      updated_at: '2026-01-01T00:00:00.000000Z',
    }

    const result = toWorkoutTemplateListItem(raw)

    expect(result).toEqual({
      id: '1',
      name: 'Template 1',
      notes: 'Notes here',
      exercises: [
        { id: '10', name: 'Bench Press', type: 'resistance' },
        { id: '11', name: 'Squats', type: 'resistance' },
      ],
    })
  })

  it('handles empty exercises and null notes', () => {
    const raw: RawWorkoutTemplateListItem = {
      id: 2,
      name: 'Empty Template',
      notes: null,
      exercises: [],
      created_at: '2026-01-01T00:00:00.000000Z',
      updated_at: '2026-01-01T00:00:00.000000Z',
    }

    const result = toWorkoutTemplateListItem(raw)

    expect(result.exercises).toEqual([])
    expect(result.notes).toBeNull()
  })
})

describe('toMetricsPayload', () => {
  it('maps load metric to wire format', () => {
    const entry = {
      id: '41',
      workoutId: '43',
      exerciseId: '906',
      setOrder: 0,
      entryGroupId: null,
      groupRound: null,
      notes: null,
      loadMetric: {
        targetWeight: 135.0,
        actualWeight: 130.0,
        bodyweightOnly: false,
      },
    }

    const result = toMetricsPayload(entry as Partial<WorkoutEntry> as WorkoutEntry)

    expect(result.load).toEqual({
      target_weight: 135.0,
      actual_weight: 130.0,
      bodyweight_only: false,
    })
    expect(result.reps).toBeUndefined()
  })

  it('maps reps metric to wire format', () => {
    const entry = {
      id: '41',
      workoutId: '43',
      exerciseId: '906',
      setOrder: 0,
      entryGroupId: null,
      groupRound: null,
      notes: null,
      repMetric: {
        targetReps: 8,
        actualReps: 8,
        toFailure: false,
        failureRep: null,
      },
    }

    const result = toMetricsPayload(entry as Partial<WorkoutEntry> as WorkoutEntry)

    expect(result.reps).toEqual({
      target_reps: 8,
      actual_reps: 8,
      to_failure: false,
      failure_rep: null,
    })
  })

  it('maps duration metric to wire format', () => {
    const entry = {
      id: '43',
      workoutId: '43',
      exerciseId: '906',
      setOrder: 2,
      entryGroupId: null,
      groupRound: null,
      notes: null,
      durationMetric: {
        targetDurationSeconds: 120,
        actualDurationSeconds: 115,
      },
    }

    const result = toMetricsPayload(entry as Partial<WorkoutEntry> as WorkoutEntry)

    expect(result.duration).toEqual({
      target_duration_seconds: 120,
      actual_duration_seconds: 115,
    })
  })

  it('maps distance metric to wire format', () => {
    const entry = {
      id: '50',
      workoutId: '43',
      exerciseId: '901',
      setOrder: 0,
      entryGroupId: null,
      groupRound: null,
      notes: null,
      distanceMetric: {
        targetDistance: 5.0,
        actualDistance: 4.8,
        lapCount: 10,
        strokeCount: 1200,
      },
    }

    const result = toMetricsPayload(entry as Partial<WorkoutEntry> as WorkoutEntry)

    expect(result.distance).toEqual({
      target_distance: 5.0,
      actual_distance: 4.8,
      lap_count: 10,
      stroke_count: 1200,
    })
  })

  it('maps cardio settings to wire format', () => {
    const entry = {
      id: '51',
      workoutId: '43',
      exerciseId: '902',
      setOrder: 0,
      entryGroupId: null,
      groupRound: null,
      notes: null,
      cardioSettings: {
        resistanceLevel: 5,
        incline: 2.5,
        speed: 8.0,
        cadence: 160,
      },
    }

    const result = toMetricsPayload(entry as Partial<WorkoutEntry> as WorkoutEntry)

    expect(result.cardio_settings).toEqual({
      resistance_level: 5,
      incline: 2.5,
      speed: 8.0,
      cadence: 160,
    })
  })

  it('maps interval header with rounds to wire format', () => {
    const entry = {
      id: '42',
      workoutId: '43',
      exerciseId: '907',
      setOrder: 1,
      entryGroupId: null,
      groupRound: null,
      notes: null,
      intervalHeader: {
        programmedRounds: 4,
        completedRounds: 4,
        targetWorkSeconds: 30,
        targetRestSeconds: 15,
        rounds: [
          {
            roundNumber: 1,
            actualWorkSeconds: 30,
            actualRestSeconds: 15,
            heartRateAvg: 140,
            heartRatePeak: 152,
          },
          {
            roundNumber: 2,
            actualWorkSeconds: 31,
            actualRestSeconds: 14,
            heartRateAvg: null,
            heartRatePeak: null,
          },
        ],
      },
    }

    const result = toMetricsPayload(entry as Partial<WorkoutEntry> as WorkoutEntry)

    expect(result.interval_header).toEqual({
      programmed_rounds: 4,
      completed_rounds: 4,
      target_work_seconds: 30,
      target_rest_seconds: 15,
      rounds: [
        {
          round_number: 1,
          actual_work_seconds: 30,
          actual_rest_seconds: 15,
          heart_rate_avg: 140,
          heart_rate_peak: 152,
        },
        {
          round_number: 2,
          actual_work_seconds: 31,
          actual_rest_seconds: 14,
          heart_rate_avg: null,
          heart_rate_peak: null,
        },
      ],
    })
  })

  it('maps intensity metric to wire format', () => {
    const entry = {
      id: '52',
      workoutId: '43',
      exerciseId: '903',
      setOrder: 0,
      entryGroupId: null,
      groupRound: null,
      notes: 'felt good',
      intensityMetric: {
        rpe: 8,
        avgHr: 145,
        maxHr: 165,
      },
    }

    const result = toMetricsPayload(entry as Partial<WorkoutEntry> as WorkoutEntry)

    expect(result.intensity).toEqual({
      rpe: 8,
      avg_hr: 145,
      max_hr: 165,
    })
  })

  it('returns empty object when no metrics present', () => {
    const entry = {
      id: '53',
      workoutId: '43',
      exerciseId: '904',
      setOrder: 0,
      entryGroupId: null,
      groupRound: null,
      notes: null,
    }

    const result = toMetricsPayload(entry as Partial<WorkoutEntry> as WorkoutEntry)

    expect(result).toEqual({})
  })

  it('includes multiple metrics when present', () => {
    const entry = {
      id: '41',
      workoutId: '43',
      exerciseId: '906',
      setOrder: 0,
      entryGroupId: null,
      groupRound: null,
      notes: null,
      loadMetric: {
        targetWeight: 135.0,
        actualWeight: 130.0,
        bodyweightOnly: false,
      },
      repMetric: {
        targetReps: 8,
        actualReps: 8,
        toFailure: false,
        failureRep: null,
      },
      intensityMetric: {
        rpe: 8,
        avgHr: 145,
        maxHr: 165,
      },
    }

    const result = toMetricsPayload(entry as Partial<WorkoutEntry> as WorkoutEntry)

    expect(Object.keys(result)).toHaveLength(3)
    expect(result.load).toBeDefined()
    expect(result.reps).toBeDefined()
    expect(result.intensity).toBeDefined()
    expect(result.duration).toBeUndefined()
  })
})
