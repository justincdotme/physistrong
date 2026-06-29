import { useQuery } from '@tanstack/react-query'
import { useApp } from '@/lib/use-app'
import { fetchProgress, fetchRecords, transformProgressData } from '@/api/progress'
import type { ExerciseProgressData } from '@/api/types'
import type { TimeRange } from '@/api/types'

const EMPTY: ExerciseProgressData = {
  points: [],
  volume: [],
  records: [],
  type: 'resistance',
}

export function useExerciseProgress(exerciseId: string, range: TimeRange) {
  const { user } = useApp()

  const progressQuery = useQuery({
    queryKey: ['exercises', exerciseId, 'progress', range],
    queryFn: () => fetchProgress(exerciseId, range),
    enabled: !!exerciseId,
  })

  const recordsQuery = useQuery({
    queryKey: ['exercises', exerciseId, 'records'],
    queryFn: () => fetchRecords(exerciseId),
    enabled: !!exerciseId,
  })

  const isLoading = progressQuery.isLoading || recordsQuery.isLoading

  let data: ExerciseProgressData = EMPTY
  if (progressQuery.data && recordsQuery.data) {
    data = transformProgressData(progressQuery.data, recordsQuery.data, user.measurementSystem)
  }

  return { data, isLoading }
}
