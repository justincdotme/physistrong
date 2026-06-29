import type { User, EquipmentType, Exercise, Workout, WorkoutEntry } from './types'

export const fixtureUser: User = {
  id: 'u1',
  firstName: 'Justin',
  lastName: 'Carter',
  email: 'justin@physistrong.app',
  measurementSystem: 'imperial',
  theme: 'light',
}

export const fixtureEquipmentTypes: EquipmentType[] = [
  { id: 'eq-barbell', name: 'Barbell', isSystem: true, usageCount: 0 },
  { id: 'eq-dumbbell', name: 'Dumbbell', isSystem: true, usageCount: 0 },
  { id: 'eq-cable', name: 'Cable Machine', isSystem: true, usageCount: 0 },
  { id: 'eq-lever', name: 'Lever Machine', isSystem: true, usageCount: 0 },
  { id: 'eq-smith', name: 'Smith Machine', isSystem: true, usageCount: 0 },
  { id: 'eq-treadmill', name: 'Treadmill', isSystem: true, usageCount: 0 },
  { id: 'eq-bike', name: 'Stationary Bike', isSystem: true, usageCount: 0 },
  { id: 'eq-rower', name: 'Rowing Machine', isSystem: true, usageCount: 0 },
  { id: 'eq-kettlebell', name: 'Kettlebell', isSystem: true, usageCount: 0 },
  { id: 'eq-band', name: 'Resistance Band', isSystem: true, usageCount: 0 },
  { id: 'eq-pullupbar', name: 'Pull-up Bar', isSystem: true, usageCount: 0 },
  { id: 'eq-bench', name: 'Bench', isSystem: true, usageCount: 0 },
  { id: 'eq-trx', name: 'TRX Straps', isSystem: false, usageCount: 0 },
]

export const fixtureExercises: Exercise[] = [
  {
    id: 'ex-bench',
    userId: 'u1',
    name: 'Barbell Bench Press',
    type: 'resistance',
    equipmentTypeId: 'eq-barbell',
    notes: 'Primary horizontal press. Pause at chest.',
    usageCount: 0,
    bodyweightBase: false,
    allowsAddedWeight: true,
    bilateral: true,
  },
  {
    id: 'ex-curl',
    userId: 'u1',
    name: 'Dumbbell Curl',
    type: 'resistance',
    equipmentTypeId: 'eq-dumbbell',
    notes: null,
    usageCount: 0,
    bodyweightBase: false,
    allowsAddedWeight: true,
    bilateral: false,
  },
  {
    id: 'ex-pushdown',
    userId: 'u1',
    name: 'Cable Tricep Pushdown',
    type: 'resistance',
    equipmentTypeId: 'eq-cable',
    notes: null,
    usageCount: 0,
    bodyweightBase: false,
    allowsAddedWeight: true,
    bilateral: true,
  },
  {
    id: 'ex-pullup',
    userId: 'u1',
    name: 'Pull-ups',
    type: 'resistance',
    equipmentTypeId: 'eq-pullupbar',
    notes: 'Full dead hang each rep.',
    usageCount: 0,
    bodyweightBase: true,
    allowsAddedWeight: true,
    bilateral: true,
  },
  {
    id: 'ex-dip',
    userId: 'u1',
    name: 'Weighted Dips',
    type: 'resistance',
    equipmentTypeId: 'eq-bench',
    notes: null,
    usageCount: 0,
    bodyweightBase: true,
    allowsAddedWeight: true,
    bilateral: true,
  },
  {
    id: 'ex-plank',
    userId: 'u1',
    name: 'Plank',
    type: 'timed_hold',
    equipmentTypeId: null,
    notes: 'Brace hard, neutral spine.',
    usageCount: 0,
  },
  {
    id: 'ex-treadmill',
    userId: 'u1',
    name: 'Treadmill Run',
    type: 'distance',
    equipmentTypeId: 'eq-treadmill',
    notes: null,
    usageCount: 0,
    tracksElevation: true,
  },
  {
    id: 'ex-rowint',
    userId: 'u1',
    name: 'Rowing Intervals',
    type: 'interval',
    equipmentTypeId: 'eq-rower',
    notes: 'Hard pulls, controlled recovery.',
    usageCount: 0,
    defaultWorkSeconds: 60,
    defaultRestSeconds: 60,
    defaultRounds: 8,
  },
  {
    id: 'ex-squat',
    userId: 'u1',
    name: 'Barbell Squat',
    type: 'resistance',
    equipmentTypeId: 'eq-barbell',
    notes: 'Below parallel.',
    usageCount: 0,
    bodyweightBase: false,
    allowsAddedWeight: true,
    bilateral: true,
  },
  {
    id: 'ex-legpress',
    userId: 'u1',
    name: 'Leg Press',
    type: 'resistance',
    equipmentTypeId: 'eq-lever',
    notes: null,
    usageCount: 0,
    bodyweightBase: false,
    allowsAddedWeight: true,
    bilateral: true,
  },
]

let _eid = 0
function eid(): string {
  return 'we-' + ++_eid
}

function resistanceEntry(
  exerciseId: string,
  setOrder: number,
  targetW: number | null,
  actualW: number | null,
  targetR: number | null,
  actualR: number | null,
  opts?: {
    groupId?: string
    groupRound?: number
    bodyweightOnly?: boolean
    toFailure?: boolean
    failureRep?: number | null
  }
): WorkoutEntry {
  return {
    id: eid(),
    workoutId: '',
    exerciseId,
    setOrder,
    entryGroupId: opts?.groupId ?? null,
    groupRound: opts?.groupRound ?? null,
    notes: null,
    loadMetric: {
      targetWeight: targetW,
      actualWeight: actualW,
      bodyweightOnly: !!opts?.bodyweightOnly,
    },
    repMetric: {
      targetReps: targetR,
      actualReps: actualR,
      toFailure: !!opts?.toFailure,
      failureRep: opts?.failureRep ?? null,
    },
  }
}

function holdEntry(
  exerciseId: string,
  setOrder: number,
  targetS: number | null,
  actualS: number | null
): WorkoutEntry {
  return {
    id: eid(),
    workoutId: '',
    exerciseId,
    setOrder,
    entryGroupId: null,
    groupRound: null,
    notes: null,
    durationMetric: { targetDurationSeconds: targetS, actualDurationSeconds: actualS },
  }
}

function distanceEntry(
  exerciseId: string,
  setOrder: number,
  targetD: number | null,
  actualD: number | null,
  durS: number | null
): WorkoutEntry {
  return {
    id: eid(),
    workoutId: '',
    exerciseId,
    setOrder,
    entryGroupId: null,
    groupRound: null,
    notes: null,
    distanceMetric: {
      targetDistance: targetD,
      actualDistance: actualD,
      distanceUnit: 'miles',
      lapCount: null,
      strokeCount: null,
    },
    durationMetric: { targetDurationSeconds: null, actualDurationSeconds: durS },
    cardioSettings: {
      resistanceLevel: null,
      incline: 1.5,
      speed: actualD ? 6.4 : null,
      cadence: null,
    },
  }
}

function intervalEntry(
  exerciseId: string,
  setOrder: number,
  rounds: number,
  completed: number
): WorkoutEntry {
  const r = []
  for (let i = 1; i <= rounds; i++) {
    const done = i <= completed
    r.push({
      roundNumber: i,
      actualWorkSeconds: done ? 60 : null,
      actualRestSeconds: done ? 60 : null,
      heartRateAvg: done ? 150 + i : null,
      heartRatePeak: done ? 165 + i : null,
    })
  }
  return {
    id: eid(),
    workoutId: '',
    exerciseId,
    setOrder,
    entryGroupId: null,
    groupRound: null,
    notes: null,
    intervalHeader: {
      programmedRounds: rounds,
      completedRounds: completed,
      targetWorkSeconds: 60,
      targetRestSeconds: 60,
      rounds: r,
    },
  }
}

function attach(entries: WorkoutEntry[], workoutId: string): WorkoutEntry[] {
  entries.forEach(e => {
    e.workoutId = workoutId
  })
  return entries
}

const benchHistory: [string, number, number][] = [
  ['2026-03-30', 165, 5],
  ['2026-04-06', 170, 5],
  ['2026-04-13', 170, 6],
  ['2026-04-22', 175, 5],
  ['2026-05-02', 175, 6],
  ['2026-05-11', 180, 5],
  ['2026-05-20', 185, 4],
  ['2026-05-30', 185, 5],
  ['2026-06-09', 190, 4],
  ['2026-06-18', 195, 3],
]

function buildFixtureWorkouts(): Workout[] {
  const workouts: Workout[] = []

  benchHistory.forEach(([date, w, reps], i) => {
    const id = 'wk-bench-' + i
    const entries = attach(
      [
        resistanceEntry('ex-bench', 0, w - 30, w - 30, 8, 8),
        resistanceEntry('ex-bench', 1, w - 15, w - 15, 6, 6),
        resistanceEntry('ex-bench', 2, w, w, reps, reps),
        resistanceEntry('ex-pushdown', 3, 50, 50, 12, 12),
        resistanceEntry('ex-curl', 4, 35, 35, 10, 10),
      ],
      id
    )
    workouts.push({
      id,
      userId: 'u1',
      name: 'Push Day',
      date,
      exhaustion: 6 + (i % 3),
      soreness: 4 + (i % 3),
      entries,
      entryGroups: [],
    })
  })

  // Complete full-body workout
  const fullId = 'wk-full-1'
  const fullEntries = attach(
    [
      resistanceEntry('ex-squat', 0, 245, 245, 5, 5),
      resistanceEntry('ex-squat', 1, 245, 245, 5, 5),
      resistanceEntry('ex-pullup', 2, 0, 0, 8, 8, { bodyweightOnly: true }),
      resistanceEntry('ex-pullup', 3, 0, 0, 8, 7, {
        toFailure: true,
        failureRep: 7,
        bodyweightOnly: true,
      }),
      resistanceEntry('ex-legpress', 4, 360, 360, 10, 10),
      holdEntry('ex-plank', 5, 60, 75),
      distanceEntry('ex-treadmill', 6, 2, 2, 1140),
      intervalEntry('ex-rowint', 7, 8, 5),
    ],
    fullId
  )
  workouts.push({
    id: fullId,
    userId: 'u1',
    name: 'Full Body',
    date: '2026-06-15',
    exhaustion: 8,
    soreness: 6,
    entries: fullEntries,
    entryGroups: [],
  })

  // In-progress workout with superset
  const ipId = 'wk-inprogress'
  const groupId = 'grp-1'
  const ipEntries = attach(
    [
      resistanceEntry('ex-bench', 0, 185, 185, 6, 6, { groupId, groupRound: 1 }),
      resistanceEntry('ex-dip', 1, 45, 45, 10, 10, { groupId, groupRound: 1 }),
      resistanceEntry('ex-bench', 2, 185, 185, 6, 5, {
        groupId,
        groupRound: 2,
        toFailure: true,
        failureRep: 5,
      }),
      resistanceEntry('ex-dip', 3, 45, null, 10, null, { groupId, groupRound: 2 }),
      resistanceEntry('ex-bench', 4, 185, null, 6, null, { groupId, groupRound: 3 }),
      resistanceEntry('ex-dip', 5, 45, null, 10, null, { groupId, groupRound: 3 }),
      resistanceEntry('ex-curl', 6, 40, null, 10, null),
      resistanceEntry('ex-curl', 7, 40, null, 10, null),
    ],
    ipId
  )
  workouts.push({
    id: ipId,
    userId: 'u1',
    name: 'Push Day',
    date: '2026-06-27',
    exhaustion: null,
    soreness: null,
    entries: ipEntries,
    entryGroups: [
      {
        id: groupId,
        workoutId: ipId,
        name: 'Chest Superset',
        plannedRounds: 3,
        restBetweenExercisesSeconds: 30,
        restBetweenRoundsSeconds: 90,
      },
    ],
  })

  // Empty workout
  const emptyId = 'wk-empty'
  const emptyEntries = attach([resistanceEntry('ex-squat', 0, null, null, 5, null)], emptyId)
  workouts.push({
    id: emptyId,
    userId: 'u1',
    name: 'Leg Day',
    date: '2026-06-25',
    exhaustion: null,
    soreness: null,
    entries: emptyEntries,
    entryGroups: [],
  })

  return workouts
}

export const fixtureWorkouts: Workout[] = buildFixtureWorkouts()
