import { useState } from 'react'
import { ChevronDown } from 'lucide-react'
import { useQuery } from '@tanstack/react-query'
import { PageHeader } from '@/components/ui/page-header'
import { SegmentedControl } from '@/components/ui/segmented-control'
import { ProgressPanel } from './exercise-progress'
import { listExercises } from '@/api/exercises'
import type { TimeRange } from '@/api/types'

const TYPES: Array<{ value: string; label: string }> = [
  { value: 'all', label: 'All' },
  { value: 'resistance', label: 'Resistance' },
  { value: 'timed_hold', label: 'Hold' },
  { value: 'distance', label: 'Distance' },
  { value: 'interval', label: 'Interval' },
]

const RANGES: Array<{ value: TimeRange; label: string }> = [
  { value: '1M', label: '1M' },
  { value: '3M', label: '3M' },
  { value: '6M', label: '6M' },
  { value: '1Y', label: '1Y' },
  { value: 'All', label: 'All' },
]

export function ProgressPage() {
  const { data: exercises = [] } = useQuery({
    queryKey: ['exercises'],
    queryFn: listExercises,
  })

  const [type, setType] = useState('all')
  const [range, setRange] = useState<TimeRange>('6M')
  const [exId, setExId] = useState('')

  const visible = exercises.filter(e => type === 'all' || e.type === type)
  const selected = exId && visible.find(e => e.id === exId) ? exId : (visible[0]?.id ?? '')

  return (
    <div dusk="progress-page">
      <PageHeader title="Progress" subtitle="Track every lift over time" />

      <div className="mb-3">
        <SegmentedControl size="sm" value={type} onChange={v => setType(v)} options={TYPES} />
      </div>

      <label className="block mb-5" dusk="exercise-picker">
        <span className="label-caps text-text-secondary block mb-1.5">Exercise</span>
        <div className="relative">
          <select
            value={selected}
            onChange={e => setExId(e.target.value)}
            className="ps-input w-full pl-3 pr-10 py-3 text-sm font-semibold appearance-none cursor-pointer"
          >
            {visible.map(e => (
              <option key={e.id} value={e.id}>
                {e.name}
              </option>
            ))}
          </select>
          <ChevronDown
            size={18}
            className="absolute right-3 top-1/2 -translate-y-1/2 text-text-muted pointer-events-none"
          />
        </div>
      </label>

      {selected ? (
        <>
          <div className="mb-5">
            <SegmentedControl
              size="sm"
              value={range}
              onChange={(v: string) => setRange(v as TimeRange)}
              options={RANGES}
            />
          </div>
          <ProgressPanel exerciseId={selected} range={range} />
        </>
      ) : (
        <div className="ps-card p-8 text-center text-text-secondary text-sm">
          No exercises to chart yet.
        </div>
      )}
    </div>
  )
}
