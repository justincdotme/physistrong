import { useCallback, useEffect, useState, type ReactNode } from 'react'
import type {
  User,
  EquipmentType,
  Exercise,
  Workout,
  WorkoutEntry,
  WorkoutTemplate,
} from '@/api/types'
import {
  fixtureUser,
  fixtureEquipmentTypes,
  fixtureExercises,
  fixtureWorkouts,
  fixtureTemplates,
} from '@/api/fixtures'
import { AppContext, type AppContextValue } from './app-context'

interface Toast {
  id: string
  message: string
}

function clone<T>(x: T): T {
  return JSON.parse(JSON.stringify(x))
}

function applyTheme(theme: string) {
  let resolved = theme
  if (theme === 'system') {
    resolved = window.matchMedia?.('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'
  }
  document.documentElement.classList.toggle('dark', resolved === 'dark')
}

let _id = 1000
function uid(p: string): string {
  return `${p}-${++_id}-${Math.floor(Math.random() * 1e4)}`
}

export function AppProvider({ children }: { children: ReactNode }) {
  const [user, setUser] = useState<User>(() => clone(fixtureUser))
  const [authed, setAuthed] = useState(false)
  const [exercises, setExercises] = useState<Exercise[]>(() => clone(fixtureExercises))
  const [equipment, setEquipment] = useState<EquipmentType[]>(() => clone(fixtureEquipmentTypes))
  const [workouts, setWorkouts] = useState<Workout[]>(() => clone(fixtureWorkouts))
  const [templates, setTemplates] = useState<WorkoutTemplate[]>(() => clone(fixtureTemplates))
  const [toasts, setToasts] = useState<Toast[]>([])

  useEffect(() => {
    applyTheme(user.theme)
    if (user.theme !== 'system') return
    const mq = window.matchMedia('(prefers-color-scheme: dark)')
    const onChange = () => applyTheme('system')
    mq.addEventListener('change', onChange)
    return () => mq.removeEventListener('change', onChange)
  }, [user.theme])

  const toast = useCallback((message: string) => {
    const id = uid('toast')
    setToasts(t => [...t, { id, message }])
    setTimeout(() => setToasts(t => t.filter(x => x.id !== id)), 2400)
  }, [])

  const updateUser = useCallback((patch: Partial<User>) => setUser(u => ({ ...u, ...patch })), [])
  const login = useCallback(() => setAuthed(true), [])
  const logout = useCallback(() => setAuthed(false), [])

  const addExercise = useCallback(
    (ex: Partial<Exercise> & { name: string; type: Exercise['type'] }): Exercise => {
      const full: Exercise = {
        id: uid('ex'),
        userId: 'u1',
        notes: null,
        equipmentTypeId: null,
        ...ex,
      }
      setExercises(xs => [full, ...xs])
      return full
    },
    []
  )
  const updateExercise = useCallback((id: string, patch: Partial<Exercise>) => {
    setExercises(xs => xs.map(x => (x.id === id ? { ...x, ...patch } : x)))
  }, [])
  const deleteExercise = useCallback(
    (id: string) => setExercises(xs => xs.filter(x => x.id !== id)),
    []
  )

  const addEquipment = useCallback((name: string): EquipmentType => {
    const eq: EquipmentType = { id: uid('eq'), name, isSystem: false }
    setEquipment(es => [eq, ...es])
    return eq
  }, [])
  const deleteEquipment = useCallback(
    (id: string) => setEquipment(es => es.filter(e => e.id !== id)),
    []
  )

  const addWorkout = useCallback(
    (w: Partial<Workout> & { name: string; date: string }): Workout => {
      const full: Workout = {
        id: uid('wk'),
        userId: 'u1',
        exhaustion: null,
        soreness: null,
        entries: [],
        entryGroups: [],
        ...w,
      }
      setWorkouts(ws => [full, ...ws])
      return full
    },
    []
  )
  const updateWorkout = useCallback((id: string, patch: Partial<Workout>) => {
    setWorkouts(ws => ws.map(w => (w.id === id ? { ...w, ...patch } : w)))
  }, [])
  const deleteWorkout = useCallback(
    (id: string) => setWorkouts(ws => ws.filter(w => w.id !== id)),
    []
  )

  const createFromTemplate = useCallback(
    (templateId: string, date: string, name?: string): Workout | null => {
      const tpl = templates.find(t => t.id === templateId)
      if (!tpl) return null
      const wid = uid('wk')
      const entries: WorkoutEntry[] = []
      const entryGroups: Workout['entryGroups'] = []
      let order = 0

      const makeEntries = (exId: string, groupId: string | null, round: number | null) => {
        const ex = exercises.find(e => e.id === exId)
        if (!ex) return
        const base = {
          id: uid('we'),
          workoutId: wid,
          exerciseId: exId,
          setOrder: order++,
          entryGroupId: groupId,
          groupRound: round,
          notes: null,
        }
        if (ex.type === 'resistance') {
          entries.push({
            ...base,
            loadMetric: {
              targetWeight: null,
              actualWeight: null,
              bodyweightOnly: !!ex.bodyweightBase,
            },
            repMetric: { targetReps: null, actualReps: null, toFailure: false, failureRep: null },
          })
        } else if (ex.type === 'timed_hold') {
          entries.push({
            ...base,
            durationMetric: { targetDurationSeconds: null, actualDurationSeconds: null },
          })
        } else if (ex.type === 'distance') {
          entries.push({
            ...base,
            distanceMetric: {
              targetDistance: null,
              actualDistance: null,
              distanceUnit: user.measurementSystem === 'imperial' ? 'miles' : 'kilometers',
              lapCount: null,
              strokeCount: null,
            },
          })
        } else if (ex.type === 'interval') {
          const rounds = []
          const n = ex.defaultRounds ?? 8
          for (let i = 1; i <= n; i++)
            rounds.push({
              roundNumber: i,
              actualWorkSeconds: null,
              actualRestSeconds: null,
              heartRateAvg: null,
              heartRatePeak: null,
            })
          entries.push({
            ...base,
            intervalHeader: {
              programmedRounds: n,
              completedRounds: 0,
              targetWorkSeconds: ex.defaultWorkSeconds ?? 60,
              targetRestSeconds: ex.defaultRestSeconds ?? 60,
              rounds,
            },
          })
        }
      }

      tpl.groups.forEach(g => {
        const gid = uid('grp')
        entryGroups.push({
          id: gid,
          workoutId: wid,
          name: g.name,
          plannedRounds: g.plannedRounds,
          restBetweenExercisesSeconds: g.restBetweenExercisesSeconds,
          restBetweenRoundsSeconds: g.restBetweenRoundsSeconds,
        })
        for (let r = 1; r <= g.plannedRounds; r++) {
          g.exercises.forEach(te => makeEntries(te.exerciseId, gid, r))
        }
      })
      tpl.exercises.forEach(te => makeEntries(te.exerciseId, null, null))

      const w: Workout = {
        id: wid,
        userId: 'u1',
        name: name ?? tpl.name,
        date,
        exhaustion: null,
        soreness: null,
        entries,
        entryGroups,
      }
      setWorkouts(ws => [w, ...ws])
      return w
    },
    [templates, exercises, user.measurementSystem]
  )

  const updateTemplate = useCallback((id: string, patch: Partial<WorkoutTemplate>) => {
    setTemplates(ts => ts.map(t => (t.id === id ? { ...t, ...patch } : t)))
  }, [])
  const deleteTemplate = useCallback(
    (id: string) => setTemplates(ts => ts.filter(t => t.id !== id)),
    []
  )

  const value: AppContextValue = {
    user,
    authed,
    exercises,
    equipment,
    workouts,
    templates,
    toasts,
    uid,
    toast,
    updateUser,
    login,
    logout,
    addExercise,
    updateExercise,
    deleteExercise,
    addEquipment,
    deleteEquipment,
    addWorkout,
    updateWorkout,
    deleteWorkout,
    createFromTemplate,
    updateTemplate,
    deleteTemplate,
  }

  return <AppContext.Provider value={value}>{children}</AppContext.Provider>
}
