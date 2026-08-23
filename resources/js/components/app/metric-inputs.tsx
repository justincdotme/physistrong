import { Trophy, Plus, ChevronDown } from 'lucide-react'
import type { WorkoutEntry, Exercise } from '@/api/types'
import { useAuth } from '@/hooks/use-auth'
import { cn, assertNever } from '@/lib/utils'
import { unitLabel } from '@/lib/units'
import { Badge } from '@/components/ui/badge'
import { formatDuration } from '@/lib/duration'
import { DurationInput } from '@/components/ui/duration-input'

interface MetricFieldProps {
  label: string
  value: number | null
  onChange: (value: number | null) => void
  unit?: string
  target?: number | null
  placeholder?: string
  big?: boolean
  disabled?: boolean
  duration?: boolean
}

function MetricField({
  label,
  value,
  onChange,
  unit,
  target,
  placeholder = '0',
  big = true,
  disabled,
  duration,
}: MetricFieldProps) {
  const numberStyle = {
    fontSize: big ? 20 : 16,
    fontWeight: 700,
    letterSpacing: '-0.5px',
    color: 'var(--color-text-primary)',
  }

  // One <label> cannot own three segment inputs, so the duration card drops to
  // a plain container and DurationInput names each segment itself.
  const Shell = duration ? 'div' : 'label'

  return (
    <Shell
      className={cn(
        'ps-metric flex flex-col items-center justify-center px-3 py-2 min-h-[64px] flex-1',
        disabled && 'opacity-60'
      )}
    >
      <span className="label-caps text-text-muted mb-0.5">{label}</span>
      {target != null && (
        <span className="text-[11px] text-text-muted -mt-0.5 mb-0.5">
          target {duration ? formatDuration(target) : target}
        </span>
      )}
      <span className="flex items-baseline gap-1">
        {duration ? (
          <DurationInput
            value={value}
            onChange={onChange}
            label={label}
            disabled={disabled}
            style={numberStyle}
          />
        ) : (
          <input
            type="number"
            inputMode="decimal"
            disabled={disabled}
            value={value == null ? '' : value}
            placeholder={placeholder}
            onChange={e => onChange(e.target.value === '' ? null : Number(e.target.value))}
            className="bg-transparent text-center outline-none w-[3.5ch] tabular-nums"
            style={numberStyle}
          />
        )}
        {unit && <span className="text-text-secondary text-xs font-medium">{unit}</span>}
      </span>
    </Shell>
  )
}

function PRBadge() {
  return (
    <Badge tone="accent">
      <Trophy size={11} />
      New PR
    </Badge>
  )
}

interface EntryMetricsProps {
  entry: WorkoutEntry
  exercise: Exercise
  allTimeBest: number | null
  onChange: (patch: Partial<WorkoutEntry>) => void
}

export function EntryMetrics({ entry, exercise, allTimeBest, onChange }: EntryMetricsProps) {
  const { user } = useAuth()
  if (!user) return null
  const unit = unitLabel(user.measurementSystem, 'weight')

  switch (exercise.type) {
    case 'resistance': {
      const lm = entry.loadMetric || {
        targetWeight: null,
        actualWeight: null,
        bodyweightOnly: false,
      }
      const rm = entry.repMetric || {
        targetReps: null,
        actualReps: null,
        toFailure: false,
        failureRep: null,
      }
      const bodyOnly = lm.bodyweightOnly || (exercise.bodyweightBase && !exercise.allowsAddedWeight)
      const showWeight = !bodyOnly
      const isPR =
        allTimeBest != null &&
        rm.actualReps != null &&
        (allTimeBest === 0 || rm.actualReps > allTimeBest)

      return (
        <div className="flex flex-col gap-2">
          <div className="flex items-stretch gap-2">
            {showWeight ? (
              <MetricField
                label={exercise.bodyweightBase ? '+ Weight' : 'Weight'}
                unit={unit}
                value={lm.actualWeight}
                target={lm.targetWeight}
                onChange={v => onChange({ loadMetric: { ...lm, actualWeight: v } })}
              />
            ) : (
              <div className="ps-metric flex flex-col items-center justify-center px-3 py-2 min-h-[64px] flex-1">
                <span className="label-caps text-text-muted mb-0.5">Load</span>
                <span className="text-sm font-semibold text-text-secondary">Bodyweight</span>
              </div>
            )}
            <MetricField
              label="Reps"
              value={rm.actualReps}
              target={rm.targetReps}
              onChange={v => onChange({ repMetric: { ...rm, actualReps: v } })}
            />
          </div>
          <div className="flex items-center justify-between flex-wrap gap-2">
            <label className="inline-flex items-center gap-2 text-[13px] text-text-secondary cursor-pointer select-none">
              <input
                type="checkbox"
                checked={!!rm.toFailure}
                onChange={e =>
                  onChange({
                    repMetric: {
                      ...rm,
                      toFailure: e.target.checked,
                      failureRep: e.target.checked ? rm.actualReps : null,
                    },
                  })
                }
                className="h-4 w-4 rounded accent-primary"
                style={{ accentColor: 'var(--color-primary)' }}
              />
              To failure
            </label>
            {isPR && <PRBadge />}
          </div>
        </div>
      )
    }
    case 'timed_hold': {
      const dm = entry.durationMetric || {
        targetDurationSeconds: null,
        actualDurationSeconds: null,
      }
      const isPR =
        allTimeBest != null &&
        dm.actualDurationSeconds != null &&
        (allTimeBest === 0 || dm.actualDurationSeconds > allTimeBest)

      return (
        <div className="flex flex-col gap-2">
          <div className="flex items-stretch gap-2">
            <MetricField
              label="Target"
              duration
              value={dm.targetDurationSeconds}
              onChange={v => onChange({ durationMetric: { ...dm, targetDurationSeconds: v } })}
            />
            <MetricField
              label="Hold"
              duration
              value={dm.actualDurationSeconds}
              onChange={v => onChange({ durationMetric: { ...dm, actualDurationSeconds: v } })}
            />
          </div>
          <div className="flex items-center justify-between">
            <span className="text-[12px] text-text-secondary">
              {dm.actualDurationSeconds != null
                ? `Logged ${formatDuration(dm.actualDurationSeconds)}`
                : 'Not logged'}
              {dm.targetDurationSeconds != null
                ? ` · target ${formatDuration(dm.targetDurationSeconds)}`
                : ''}
            </span>
            {isPR && <PRBadge />}
          </div>
        </div>
      )
    }
    case 'distance': {
      const dist = entry.distanceMetric || {
        targetDistance: null,
        actualDistance: null,
        lapCount: null,
        strokeCount: null,
      }
      const dur = entry.durationMetric || {
        targetDurationSeconds: null,
        actualDurationSeconds: null,
      }
      const du = unitLabel(user.measurementSystem, 'distance')
      const isPR =
        allTimeBest != null &&
        dist.actualDistance != null &&
        (allTimeBest === 0 || dist.actualDistance > allTimeBest)

      return (
        <div className="flex flex-col gap-2">
          <div className="flex items-stretch gap-2">
            <MetricField
              label="Distance"
              unit={du}
              value={dist.actualDistance}
              target={dist.targetDistance}
              onChange={v => onChange({ distanceMetric: { ...dist, actualDistance: v } })}
            />
            <MetricField
              label="Time"
              duration
              value={dur.actualDurationSeconds}
              onChange={v => onChange({ durationMetric: { ...dur, actualDurationSeconds: v } })}
            />
          </div>
          {isPR && (
            <div className="self-end">
              <PRBadge />
            </div>
          )}
        </div>
      )
    }
    case 'interval': {
      const ih = entry.intervalHeader || {
        programmedRounds: 1,
        completedRounds: 0,
        targetWorkSeconds: 30,
        targetRestSeconds: 15,
        rounds: [],
      }
      const done = ih.completedRounds || 0
      const total = ih.programmedRounds || 1
      const setRounds = (n: number) =>
        onChange({ intervalHeader: { ...ih, completedRounds: Math.max(0, Math.min(total, n)) } })

      return (
        <div className="ps-metric px-4 py-3 flex items-center justify-between gap-3">
          <div>
            <div className="label-caps text-text-muted mb-0.5">Rounds</div>
            <div
              className="tabular-nums"
              style={{ fontSize: 20, fontWeight: 700, letterSpacing: '-0.5px' }}
            >
              {done} <span className="text-text-secondary text-sm font-medium">of {total}</span>
            </div>
            <div className="text-[12px] text-text-secondary mt-0.5">
              {formatDuration(ih.targetWorkSeconds)} work · {formatDuration(ih.targetRestSeconds)}{' '}
              rest
            </div>
          </div>
          <div className="flex items-center gap-2">
            <button
              aria-label="One fewer round"
              onClick={() => setRounds(done - 1)}
              className="h-11 w-11 rounded-lg border border-border-strong flex items-center justify-center text-text-secondary hover:bg-surface-card"
            >
              <ChevronDown size={20} />
            </button>
            <button
              aria-label="One more round"
              onClick={() => setRounds(done + 1)}
              className="h-11 w-11 rounded-lg flex items-center justify-center text-white"
              style={{ backgroundImage: 'var(--gradient-primary)' }}
            >
              <Plus size={20} />
            </button>
          </div>
        </div>
      )
    }
    default:
      return assertNever(exercise.type)
  }
}
