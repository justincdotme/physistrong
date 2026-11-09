import { useQuery } from '@tanstack/react-query'
import { useAuth } from '@/hooks/use-auth'
import { exerciseQueries } from '@/api/exercises'
import { transformProgressData } from '@/api/progress'
import type { ExerciseProgressData } from '@/api/types'
import type { TimeRange } from '@/api/types'

const EMPTY: ExerciseProgressData = {
  series: [],
  volume: [],
  records: [],
  type: 'resistance',
  primaryMetric: 'weight',
}

export function useExerciseProgress(exerciseId: string, range: TimeRange) {
  const { user } = useAuth()

  const progressQuery = useQuery({
    ...exerciseQueries.progress(exerciseId, range),
    enabled: !!exerciseId,
  })

  const recordsQuery = useQuery({
    ...exerciseQueries.records(exerciseId),
    enabled: !!exerciseId,
  })

  const isLoading = progressQuery.isLoading || recordsQuery.isLoading

  let data: ExerciseProgressData = EMPTY
  if (progressQuery.data && recordsQuery.data && user) {
    data = transformProgressData(progressQuery.data, recordsQuery.data, user.measurementSystem)
  }

  return { data, isLoading }
}
