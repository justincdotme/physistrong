import { useApp } from '@/lib/store'
import { formatDuration } from '@/lib/formatters'
import type { ExerciseProgressData, TimeRange } from '@/api/types'

interface UseExerciseProgressResult {
  data: ExerciseProgressData
  loading: boolean
  error: null
}

export function useExerciseProgress(
  exerciseId: string,
  range: TimeRange
): UseExerciseProgressResult {
  const { workouts, exercises, user } = useApp()
  const ex = exercises.find(e => e.id === exerciseId)
  if (!ex)
    return {
      data: { points: [], volume: [], records: [], type: 'resistance' },
      loading: false,
      error: null,
    }

  const rows: {
    date: string
    value: number
    reps: number | null
    entryId: string
    volume: number
  }[] = []
  workouts.forEach(w => {
    const es = w.entries.filter(e => e.exerciseId === exerciseId)
    if (!es.length) return
    let value: number | null = null
    let reps: number | null = null
    let entryId: string = ''
    let volume = 0
    es.forEach(e => {
      if (ex.type === 'resistance' && e.loadMetric && e.loadMetric.actualWeight != null) {
        if (value == null || e.loadMetric.actualWeight > value) {
          value = e.loadMetric.actualWeight
          reps = e.repMetric ? e.repMetric.actualReps : null
          entryId = e.id
        }
        if (e.repMetric && e.repMetric.actualReps != null)
          volume += e.loadMetric.actualWeight * e.repMetric.actualReps
      } else if (
        ex.type === 'timed_hold' &&
        e.durationMetric &&
        e.durationMetric.actualDurationSeconds != null
      ) {
        if (value == null || e.durationMetric.actualDurationSeconds > value) {
          value = e.durationMetric.actualDurationSeconds
          entryId = e.id
        }
      } else if (
        ex.type === 'distance' &&
        e.distanceMetric &&
        e.distanceMetric.actualDistance != null
      ) {
        if (value == null || e.distanceMetric.actualDistance > value) {
          value = e.distanceMetric.actualDistance
          entryId = e.id
        }
      } else if (ex.type === 'interval' && e.intervalHeader) {
        if (value == null || e.intervalHeader.completedRounds > value) {
          value = e.intervalHeader.completedRounds
          entryId = e.id
        }
      }
    })
    if (value != null) rows.push({ date: w.date, value, reps, entryId, volume })
  })

  rows.sort((a, b) => (a.date < b.date ? -1 : 1))

  const now = new Date('2026-06-27')
  const cutoffs: Record<string, number> = { '1M': 1, '3M': 3, '6M': 6, '1Y': 12 }
  let filtered = rows
  if (range && range !== 'All' && cutoffs[range]) {
    const cut = new Date(now)
    const months = cutoffs[range] ?? 1
    cut.setMonth(cut.getMonth() - months)
    const cutISO = cut.toISOString().slice(0, 10)
    filtered = rows.filter(r => r.date >= cutISO)
  }

  let runMax = -Infinity
  const points = filtered.map(r => {
    const isPR = r.value > runMax
    if (isPR) runMax = r.value
    return { ...r, isPR }
  })
  const volume = filtered.map(r => ({ date: r.date, value: r.volume }))

  const records: ExerciseProgressData['records'] = []
  if (ex.type === 'resistance') {
    let maxW = -Infinity
    let maxWReps: number | null = null
    let maxReps = -Infinity
    let maxVol = -Infinity
    workouts.forEach(w =>
      w.entries.forEach(e => {
        if (e.exerciseId !== exerciseId) return
        if (e.loadMetric && e.loadMetric.actualWeight != null) {
          if (e.loadMetric.actualWeight > maxW) {
            maxW = e.loadMetric.actualWeight
            maxWReps = e.repMetric ? e.repMetric.actualReps : null
          }
          if (e.repMetric && e.repMetric.actualReps != null) {
            maxReps = Math.max(maxReps, e.repMetric.actualReps)
            maxVol = Math.max(maxVol, e.loadMetric.actualWeight * e.repMetric.actualReps)
          }
        }
      })
    )
    if (maxW > -Infinity)
      records.push({
        label: 'Heaviest',
        value: `${maxW}`,
        unit: user.weightUnit,
        sub: maxWReps ? `× ${maxWReps} reps` : null,
      })
    if (maxReps > -Infinity)
      records.push({ label: 'Most reps', value: `${maxReps}`, unit: 'reps', sub: null })
    if (maxVol > -Infinity)
      records.push({
        label: 'Top set volume',
        value: `${maxVol.toLocaleString()}`,
        unit: user.weightUnit,
        sub: null,
      })
  } else if (ex.type === 'timed_hold') {
    const m = Math.max(...rows.map(r => r.value), 0)
    if (m) records.push({ label: 'Longest hold', value: formatDuration(m), unit: '', sub: null })
  } else if (ex.type === 'distance') {
    const m = Math.max(...rows.map(r => r.value), 0)
    if (m)
      records.push({
        label: 'Farthest',
        value: `${m}`,
        unit: user.distanceUnit === 'miles' ? 'mi' : 'km',
        sub: null,
      })
  } else if (ex.type === 'interval') {
    const m = Math.max(...rows.map(r => r.value), 0)
    if (m) records.push({ label: 'Most rounds', value: `${m}`, unit: 'rounds', sub: null })
  }

  return { data: { points, volume, records, type: ex.type }, loading: false, error: null }
}
