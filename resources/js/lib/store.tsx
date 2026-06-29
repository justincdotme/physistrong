import { useCallback, useEffect, useState, type ReactNode } from 'react'
import type { User, EquipmentType, Exercise, Workout } from '@/api/types'
import {
  fixtureUser,
  fixtureEquipmentTypes,
  fixtureExercises,
  fixtureWorkouts,
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
        usageCount: 0,
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
    const eq: EquipmentType = { id: uid('eq'), name, isSystem: false, usageCount: 0 }
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

  const value: AppContextValue = {
    user,
    authed,
    exercises,
    equipment,
    workouts,
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
  }

  return <AppContext.Provider value={value}>{children}</AppContext.Provider>
}
