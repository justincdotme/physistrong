import { useState } from 'react'
import { useParams, useNavigate } from 'react-router-dom'
import { Trophy } from 'lucide-react'
import { useQuery } from '@tanstack/react-query'
import { PageHeader } from '@/components/ui/page-header'
import { SegmentedControl } from '@/components/ui/segmented-control'
import { ProgressLineChart, VolumeBarChart } from '@/components/app/charts'
import { useExerciseProgress } from '@/hooks/use-exercise-progress'
import { getExercise } from '@/api/exercises'
import { formatDuration } from '@/lib/formatters'
import type { TimeRange } from '@/api/types'

const RANGES: Array<{ value: TimeRange; label: string }> = [
  { value: '1M', label: '1M' },
  { value: '3M', label: '3M' },
  { value: '6M', label: '6M' },
  { value: '1Y', label: '1Y' },
  { value: 'All', label: 'All' },
]

export function ProgressPanel({ exerciseId, range }: { exerciseId: string; range: TimeRange }) {
  const { data: ex } = useQuery({
    queryKey: ['exercises', exerciseId],
    queryFn: () => getExercise(exerciseId),
    enabled: !!exerciseId,
  })
  const { data, isLoading } = useExerciseProgress(exerciseId, range)

  if (!ex || isLoading) return null

  const isResistance = ex.type === 'resistance'
  const isHold = ex.type === 'timed_hold'
  const yLabel = isResistance
    ? 'Top set (lb)'
    : isHold
      ? 'Duration'
      : ex.type === 'distance'
        ? 'Distance'
        : 'Rounds'
  const valueFormatter = isHold ? (v: number) => formatDuration(Math.round(v)) : undefined
  const unit = isResistance
    ? 'lb'
    : ex.type === 'distance'
      ? 'mi'
      : ex.type === 'interval'
        ? 'rounds'
        : ''

  return (
    <>
      <div className="ps-card p-4 mb-4">
        <div className="flex items-center justify-between mb-3">
          <span className="label-caps text-text-secondary">{yLabel} over time</span>
          <span className="inline-flex items-center gap-1 text-[12px] text-text-secondary">
            <span
              className="h-2 w-2 rounded-full inline-block"
              style={{ background: 'var(--color-accent)' }}
            />
            PR
          </span>
        </div>
        <ProgressLineChart points={data.points} valueFormatter={valueFormatter} unit={unit} />
        {data.points.length > 0 &&
          (() => {
            const latest = data.points[data.points.length - 1]
            return latest ? (
              <p className="sr-only">
                {data.points.length} data points. Latest{' '}
                {valueFormatter ? valueFormatter(latest.value) : latest.value} {unit}.
              </p>
            ) : null
          })()}
      </div>

      {isResistance && (
        <div className="ps-card p-4 mb-4">
          <span className="label-caps text-text-secondary block mb-3">
            Volume per workout (weight × reps)
          </span>
          <VolumeBarChart data={data.volume} />
        </div>
      )}

      {data.records.length > 0 && (
        <section>
          <h2 className="text-[18px] font-semibold mb-3 mt-2">Personal Records</h2>
          <div className="grid grid-cols-2 gap-3">
            {data.records.map(r => (
              <div key={r.label} className="ps-card p-4">
                <div className="label-caps text-text-muted mb-1.5 flex items-center gap-1.5">
                  <Trophy size={14} className="text-accent" /> {r.label}
                </div>
                <div className="flex items-baseline gap-1">
                  <span
                    style={{
                      fontSize: 28,
                      fontWeight: 700,
                      letterSpacing: '-0.5px',
                      color: 'var(--color-accent)',
                    }}
                    className="tabular-nums"
                  >
                    {r.value}
                  </span>
                  {r.unit && (
                    <span className="text-sm font-medium text-text-secondary">{r.unit}</span>
                  )}
                </div>
                {r.sub && <div className="text-[12px] text-text-secondary mt-0.5">{r.sub}</div>}
              </div>
            ))}
          </div>
        </section>
      )}

      {data.points.length === 0 && !isLoading && (
        <div className="ps-card p-8 text-center text-text-secondary text-sm">
          No logged sets for this exercise yet. Progress appears as you train.
        </div>
      )}
    </>
  )
}

export function ExerciseProgressPage() {
  const { id } = useParams<{ id: string }>()
  const { data: ex, isLoading } = useQuery({
    queryKey: ['exercises', id],
    queryFn: () => {
      if (!id) throw new Error('Exercise ID is required')
      return getExercise(id)
    },
    enabled: !!id,
  })
  const navigate = useNavigate()
  const [range, setRange] = useState<TimeRange>('6M')

  if (isLoading) {
    return (
      <>
        <PageHeader back onBack={() => navigate(`/exercises/${id}`)} title="Loading..." />
        <p className="text-text-secondary text-sm">Loading exercise...</p>
      </>
    )
  }

  if (!ex) {
    return <div className="py-16 text-center text-text-muted">Exercise not found.</div>
  }

  return (
    <>
      <PageHeader
        back
        onBack={() => navigate(`/exercises/${id}`)}
        title={ex.name}
        subtitle="Progress"
      />
      <div className="mb-5">
        <SegmentedControl
          size="sm"
          value={range}
          onChange={(v: string) => setRange(v as TimeRange)}
          options={RANGES}
        />
      </div>
      <ProgressPanel exerciseId={ex.id} range={range} />
    </>
  )
}
