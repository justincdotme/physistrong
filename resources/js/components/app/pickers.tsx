import { useState, useEffect } from 'react'
import { useQuery, useMutation } from '@tanstack/react-query'
import { Search, Plus, ChevronRight, ChevronLeft, Grid, Copy } from 'lucide-react'
import type { Exercise, EquipmentType, WorkoutListItem } from '@/api/types'
import { exerciseQueries } from '@/api/exercises'
import { equipmentQueries } from '@/api/equipment'
import { templateQueries, cloneTemplate } from '@/api/templates'
import { workoutQueries, copyWorkout } from '@/api/workouts'
import { Sheet } from '@/components/ui/sheet'
import { Button } from '@/components/ui/button'
import { TypeBadge } from '@/components/ui/type-badge'
import { SegmentedControl } from '@/components/ui/segmented-control'
import { DurationInput } from '@/components/ui/duration-input'
import { cn } from '@/lib/utils'
import { useApp } from '@/lib/use-app'
import { todayISO, formatDate } from '@/lib/formatters'
import { TYPE_OPTIONS } from '@/lib/exercise-types'

function equipmentLabel(equipment: EquipmentType[], equipmentTypeId: string | null): string {
  if (!equipmentTypeId) return 'No equipment'
  const eq = equipment.find(e => e.id === equipmentTypeId)
  return eq?.name || 'Unknown'
}

interface ExercisePickerProps {
  open: boolean
  onClose: () => void
  onSelect: (exercise: Exercise) => void
}

export function ExercisePicker({ open, onClose, onSelect }: ExercisePickerProps) {
  const { data: exercises = [] } = useQuery(exerciseQueries.list())
  const { data: equipment = [] } = useQuery(equipmentQueries.list())
  const [q, setQ] = useState('')
  const [type, setType] = useState('all')

  const filtered = exercises.filter(
    e => (type === 'all' || e.type === type) && e.name.toLowerCase().includes(q.toLowerCase())
  )

  return (
    <Sheet open={open} onClose={onClose} title="Add Exercise">
      <div className="relative mb-3">
        <Search size={18} className="absolute left-3 top-1/2 -translate-y-1/2 text-text-muted" />
        <input
          id="exercise-search"
          value={q}
          onChange={e => setQ(e.target.value)}
          placeholder="Search exercises"
          className="ps-input w-full pl-10 pr-3 py-2.5 text-sm"
        />
      </div>
      <div className="mb-3">
        <SegmentedControl
          size="sm"
          value={type}
          onChange={setType}
          options={[{ value: 'all', label: 'All' }, ...TYPE_OPTIONS]}
        />
      </div>
      <div className="flex flex-col gap-1.5">
        {filtered.map(ex => (
          <button
            key={ex.id}
            onClick={() => {
              onSelect(ex)
              onClose()
            }}
            className="flex items-center gap-3 p-3 rounded-lg hover:bg-surface-muted text-left min-h-[56px]"
          >
            <div className="min-w-0 flex-1">
              <div className="font-semibold text-sm truncate">{ex.name}</div>
              <div className="text-[12px] text-text-secondary truncate">
                {equipmentLabel(equipment, ex.equipmentTypeId)}
              </div>
            </div>
            <TypeBadge type={ex.type} />
            <Plus size={18} className="text-primary shrink-0" />
          </button>
        ))}
        {!filtered.length && (
          <p className="text-text-muted text-sm text-center py-6">No matching exercises.</p>
        )}
      </div>
    </Sheet>
  )
}

interface NewWorkoutWizardProps {
  open: boolean
  onClose: () => void
  onCreateEmpty: (config: { name: string; date: string }) => void
  onCloneSuccess?: (workoutId: string) => void
  onCopySuccess?: (workoutId: string) => void
}

export function NewWorkoutWizard({
  open,
  onClose,
  onCreateEmpty,
  onCloneSuccess,
  onCopySuccess,
}: NewWorkoutWizardProps) {
  const { toast } = useApp()
  const [step, setStep] = useState<
    'method' | 'details' | 'template-picker' | 'template-date' | 'workout-picker' | 'workout-date'
  >('method')
  const [name, setName] = useState('')
  const [date, setDate] = useState(todayISO())
  const [selectedTemplate, setSelectedTemplate] = useState<string | null>(null)
  const [selectedWorkout, setSelectedWorkout] = useState<WorkoutListItem | null>(null)

  const { data: templates = [] } = useQuery(templateQueries.list())

  const { data: recentWorkouts } = useQuery({
    ...workoutQueries.picker(),
    enabled: step === 'workout-picker',
  })

  const cloneMutation = useMutation({
    mutationFn: (payload: { templateId: string; date: string; name?: string }) =>
      cloneTemplate(payload.templateId, { date: payload.date, name: payload.name }),
    onSuccess: workout => {
      onClose()
      onCloneSuccess?.(workout.id)
    },
    onError: () => toast('Could not create workout from template. Try again.', 'error'),
  })

  const copyMutation = useMutation({
    mutationFn: (payload: { workoutId: string; date: string; name?: string }) =>
      copyWorkout(payload.workoutId, { date: payload.date, name: payload.name }),
    onSuccess: workout => {
      onClose()
      onCopySuccess?.(workout.id)
    },
    onError: () => toast('Could not copy workout. Try again.', 'error'),
  })

  useEffect(() => {
    if (open) {
      setStep('method')
      setName('')
      setDate(todayISO())
      setSelectedTemplate(null)
      setSelectedWorkout(null)
    }
  }, [open])

  const title =
    step === 'method'
      ? 'New Workout'
      : step === 'template-picker'
        ? 'Choose Template'
        : step === 'workout-picker'
          ? 'Copy Previous Workout'
          : 'Workout Details'

  const back =
    step === 'details'
      ? () => setStep('method')
      : step === 'template-picker'
        ? () => setStep('method')
        : step === 'template-date'
          ? () => setStep('template-picker')
          : step === 'workout-picker'
            ? () => setStep('method')
            : step === 'workout-date'
              ? () => setStep('workout-picker')
              : null

  const chooseScratch = () => {
    setName('')
    setStep('details')
  }

  const finish = () => {
    onCreateEmpty({ name: name.trim(), date })
    onClose()
  }

  const footer =
    step === 'details' ? (
      <Button full disabled={!name.trim()} onClick={finish}>
        Start Workout
      </Button>
    ) : step === 'template-date' ? (
      <Button
        full
        disabled={cloneMutation.isPending}
        onClick={() => {
          if (selectedTemplate) {
            cloneMutation.mutate({
              templateId: selectedTemplate,
              date,
              name: name.trim() || undefined,
            })
          }
        }}
      >
        {cloneMutation.isPending ? 'Creating...' : 'Start Workout'}
      </Button>
    ) : step === 'workout-date' ? (
      <Button
        full
        disabled={copyMutation.isPending}
        onClick={() => {
          if (selectedWorkout) {
            copyMutation.mutate({
              workoutId: selectedWorkout.id,
              date,
              name: name.trim() || undefined,
            })
          }
        }}
      >
        {copyMutation.isPending ? 'Copying...' : 'Start Workout'}
      </Button>
    ) : null

  interface MethodButtonProps {
    icon: React.ReactNode
    label: string
    sub: string
    onClick?: () => void
    disabled?: boolean
    badge?: string
  }

  function MethodButton({ icon, label, sub, onClick, disabled, badge }: MethodButtonProps) {
    return (
      <button
        onClick={disabled ? undefined : onClick}
        disabled={disabled}
        className={cn(
          'ps-card w-full p-4 flex items-center gap-3 text-left min-h-[68px]',
          disabled ? 'opacity-60 cursor-not-allowed' : 'hover:bg-surface-muted'
        )}
      >
        <span
          className="h-10 w-10 rounded-lg flex items-center justify-center shrink-0"
          style={{
            background: 'color-mix(in srgb, var(--color-primary) 12%, transparent)',
            color: 'var(--color-primary)',
          }}
        >
          {icon}
        </span>
        <span className="min-w-0 flex-1">
          <span className="flex items-center gap-2">
            <span className="font-semibold text-sm">{label}</span>
            {badge && (
              <span className="label-caps px-2 py-0.5 rounded-full bg-surface-muted text-text-secondary">
                {badge}
              </span>
            )}
          </span>
          <span className="block text-[12px] text-text-secondary">{sub}</span>
        </span>
        {!disabled && <ChevronRight size={18} className="text-text-muted shrink-0" />}
      </button>
    )
  }

  return (
    <Sheet open={open} onClose={onClose} title={title} footer={footer}>
      {back && (
        <button
          onClick={back}
          className="inline-flex items-center gap-1 text-[13px] font-semibold text-text-secondary hover:text-text-primary -mt-1 mb-3"
        >
          <ChevronLeft size={16} /> Back
        </button>
      )}

      {step === 'method' && (
        <div className="flex flex-col gap-2.5">
          <MethodButton
            icon={<Plus size={20} />}
            label="Start from scratch"
            sub="Build it set by set"
            onClick={chooseScratch}
          />
          <MethodButton
            icon={<Grid size={20} />}
            label="Use a template"
            sub="Start from a saved routine"
            onClick={() => setStep('template-picker')}
            disabled={templates.length === 0}
          />
          <MethodButton
            icon={<Copy size={20} />}
            label="Copy a previous workout"
            sub="Repeat a past session"
            onClick={() => setStep('workout-picker')}
          />
        </div>
      )}

      {step === 'template-picker' && (
        <div className="flex flex-col gap-2">
          {templates.map(tpl => (
            <button
              key={tpl.id}
              onClick={() => {
                setSelectedTemplate(tpl.id)
                setName('')
                setDate(todayISO())
                setStep('template-date')
              }}
              className="ps-card w-full p-4 flex items-center gap-3 text-left min-h-[68px] hover:bg-surface-muted"
            >
              <div className="min-w-0 flex-1">
                <div className="font-semibold text-sm truncate">{tpl.name}</div>
                <div className="text-[12px] text-text-secondary">
                  {tpl.exercises.length} exercise{tpl.exercises.length !== 1 ? 's' : ''}
                </div>
              </div>
              <ChevronRight size={18} className="text-text-muted shrink-0" />
            </button>
          ))}
          {!templates.length && (
            <p className="text-text-muted text-sm text-center py-6">No templates yet.</p>
          )}
        </div>
      )}

      {step === 'template-date' && (
        <div className="flex flex-col gap-4">
          <div>
            <label htmlFor="clone-name" className="form-label">
              Workout name{' '}
              <span className="normal-case tracking-normal text-text-muted">
                (optional override)
              </span>
            </label>
            <input
              id="clone-name"
              value={name}
              onChange={e => setName(e.target.value)}
              placeholder="Use template name"
              className="ps-input w-full px-3 py-2.5 text-sm"
            />
          </div>
          <div>
            <label htmlFor="clone-date" className="form-label">
              Date
            </label>
            <input
              id="clone-date"
              type="date"
              value={date}
              onChange={e => setDate(e.target.value)}
              className="ps-input w-full px-3 py-2.5 text-sm"
            />
          </div>
        </div>
      )}

      {step === 'workout-picker' && (
        <div className="flex flex-col gap-2">
          {(recentWorkouts?.items ?? []).map(w => (
            <button
              key={w.id}
              onClick={() => {
                setSelectedWorkout(w)
                setName('')
                setDate(todayISO())
                setStep('workout-date')
              }}
              className="ps-card w-full p-4 flex items-center gap-3 text-left min-h-[68px] hover:bg-surface-muted"
            >
              <div className="min-w-0 flex-1">
                <div className="font-semibold text-sm truncate">{w.name}</div>
                <div className="text-[12px] text-text-secondary">
                  {formatDate(w.date)} &middot; {w.exercises.length} exercise
                  {w.exercises.length !== 1 ? 's' : ''}
                </div>
              </div>
              <ChevronRight size={18} className="text-text-muted shrink-0" />
            </button>
          ))}
          {!(recentWorkouts?.items ?? []).length && (
            <p className="text-text-muted text-sm text-center py-6">No workouts yet.</p>
          )}
        </div>
      )}

      {step === 'workout-date' && (
        <div className="flex flex-col gap-4">
          <div>
            <label htmlFor="copy-name" className="form-label">
              Workout name{' '}
              <span className="normal-case tracking-normal text-text-muted">
                (optional override)
              </span>
            </label>
            <input
              id="copy-name"
              value={name}
              onChange={e => setName(e.target.value)}
              placeholder="Use workout name"
              className="ps-input w-full px-3 py-2.5 text-sm"
            />
          </div>
          <div>
            <label htmlFor="copy-date" className="form-label">
              Date
            </label>
            <input
              id="copy-date"
              type="date"
              value={date}
              onChange={e => setDate(e.target.value)}
              className="ps-input w-full px-3 py-2.5 text-sm"
            />
          </div>
        </div>
      )}

      {step === 'details' && (
        <div className="flex flex-col gap-4">
          <div>
            <label htmlFor="workout-name" className="form-label">
              Workout name
            </label>
            <input
              id="workout-name"
              value={name}
              onChange={e => setName(e.target.value)}
              placeholder="e.g. Push Day"
              className="ps-input w-full px-3 py-2.5 text-sm"
            />
          </div>
          <div>
            <label htmlFor="workout-date" className="form-label">
              Date
            </label>
            <input
              id="workout-date"
              type="date"
              value={date}
              onChange={e => setDate(e.target.value)}
              className="ps-input w-full px-3 py-2.5 text-sm"
            />
          </div>
        </div>
      )}
    </Sheet>
  )
}

interface GroupConfigSheetProps {
  open: boolean
  onClose: () => void
  count: number
  onConfirm: (config: {
    name: string | null
    plannedRounds: number
    restBetweenExercisesSeconds: number
    restBetweenRoundsSeconds: number
  }) => void
}

interface NumInputProps {
  label: string
  value: number
  set: (value: number) => void
  suffix?: string
}

function NumInput({ label, value, set, suffix }: NumInputProps) {
  return (
    <div>
      <label className="form-label">{label}</label>
      <div className="flex items-center gap-1">
        <input
          type="number"
          value={value}
          onChange={e => set(Number(e.target.value))}
          className="ps-input w-full px-3 py-2.5 text-sm tabular-nums"
        />
        {suffix && <span className="text-text-muted text-sm w-6">{suffix}</span>}
      </div>
    </div>
  )
}

export function GroupConfigSheet({ open, onClose, count, onConfirm }: GroupConfigSheetProps) {
  const [name, setName] = useState('')
  const [rounds, setRounds] = useState(3)
  const [restEx, setRestEx] = useState(30)
  const [restRound, setRestRound] = useState(90)

  useEffect(() => {
    if (open) {
      setName('')
      setRounds(3)
      setRestEx(30)
      setRestRound(90)
    }
  }, [open])

  return (
    <Sheet
      open={open}
      onClose={onClose}
      title={`Group ${count} Exercises`}
      footer={
        <Button
          full
          onClick={() => {
            onConfirm({
              name: name.trim() || null,
              plannedRounds: rounds,
              restBetweenExercisesSeconds: restEx,
              restBetweenRoundsSeconds: restRound,
            })
            onClose()
          }}
        >
          Create Group
        </Button>
      }
    >
      <p className="text-text-secondary text-sm mb-4">
        These exercises will run as a superset. One round cycles through each before resting.
      </p>
      <div className="flex flex-col gap-4">
        <div>
          <label className="form-label">
            Group name{' '}
            <span className="normal-case tracking-normal text-text-muted">(optional)</span>
          </label>
          <input
            value={name}
            onChange={e => setName(e.target.value)}
            placeholder="e.g. Chest Superset"
            className="ps-input w-full px-3 py-2.5 text-sm"
          />
        </div>
        <NumInput label="Rounds" value={rounds} set={setRounds} />
        <div className="grid grid-cols-2 gap-3">
          <div>
            <span className="form-label">Rest between</span>
            <DurationInput
              label="Rest between"
              value={restEx}
              onChange={seconds => setRestEx(seconds ?? 0)}
              className="ps-input w-full px-3 py-2.5 text-sm"
            />
          </div>
          <div>
            <span className="form-label">Rest / round</span>
            <DurationInput
              label="Rest per round"
              value={restRound}
              onChange={seconds => setRestRound(seconds ?? 0)}
              className="ps-input w-full px-3 py-2.5 text-sm"
            />
          </div>
        </div>
      </div>
    </Sheet>
  )
}
