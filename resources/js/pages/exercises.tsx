import { useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { Search, Plus, Trash2 } from 'lucide-react'
import { PageHeader } from '@/components/ui/page-header'
import { Button } from '@/components/ui/button'
import { Card } from '@/components/ui/card'
import { TypeBadge } from '@/components/ui/type-badge'
import { SegmentedControl } from '@/components/ui/segmented-control'
import { EmptyState } from '@/components/ui/empty-state'
import { ConfirmDialog } from '@/components/ui/confirm-dialog'
import { Sheet } from '@/components/ui/sheet'
import { Toggle } from '@/components/ui/toggle'
import { useExercises } from '@/hooks/use-exercises'
import { useEquipment } from '@/hooks/use-equipment'
import { useWorkouts } from '@/hooks/use-workouts'
import { useApp } from '@/lib/use-app'
import { equipmentName, exerciseUsageCount, TYPE_LABELS } from '@/lib/domain'
import type { Exercise, ExerciseType, EquipmentType } from '@/api/types'

const TYPES: Array<{ value: string; label: string }> = [
  { value: 'all', label: 'All' },
  { value: 'resistance', label: 'Resistance' },
  { value: 'timed_hold', label: 'Hold' },
  { value: 'distance', label: 'Distance' },
  { value: 'interval', label: 'Interval' },
]

function CreateExerciseSheet({ open, onClose }: { open: boolean; onClose: () => void }) {
  const { addExercise, toast } = useApp()
  const { data: equipment } = useEquipment()
  const navigate = useNavigate()
  const [name, setName] = useState('')
  const [type, setType] = useState<ExerciseType>('resistance')
  const [equip, setEquip] = useState('')
  const [bodyweight, setBodyweight] = useState(false)
  const [addedWeight, setAddedWeight] = useState(true)
  const [bilateral, setBilateral] = useState(true)

  if (open && name === '') {
    setName('')
    setType('resistance')
    setEquip('')
    setBodyweight(false)
    setAddedWeight(true)
    setBilateral(true)
  }

  const create = () => {
    const ex: Partial<Exercise> & { name: string; type: ExerciseType } = {
      name: name.trim(),
      type,
      equipmentTypeId: equip || null,
    }
    if (type === 'resistance') {
      Object.assign(ex, { bodyweightBase: bodyweight, allowsAddedWeight: addedWeight, bilateral })
    }
    if (type === 'distance') {
      Object.assign(ex, { tracksElevation: false })
    }
    if (type === 'interval') {
      Object.assign(ex, { defaultWorkSeconds: 60, defaultRestSeconds: 60, defaultRounds: 8 })
    }
    const created = addExercise(ex)
    onClose()
    toast('Exercise created.')
    navigate(`/exercises/${created.id}`)
  }

  return (
    <Sheet
      open={open}
      onClose={onClose}
      title="Create Exercise"
      footer={
        <Button full disabled={!name.trim()} onClick={create}>
          Create Exercise
        </Button>
      }
    >
      <div className="flex flex-col gap-4">
        <div>
          <label htmlFor="exercise-name" className="label-caps text-text-secondary block mb-1.5">
            Name
          </label>
          <input
            id="exercise-name"
            value={name}
            onChange={e => setName(e.target.value)}
            placeholder="e.g. Incline Bench Press"
            className="ps-input w-full px-3 py-2.5 text-sm"
          />
        </div>
        <div>
          {/* eslint-disable-next-line jsx-a11y/label-has-associated-control */}
          <label id="exercise-type-label" className="label-caps text-text-secondary block mb-1.5">
            Type
          </label>
          <div
            className="grid grid-cols-2 gap-2"
            role="group"
            aria-labelledby="exercise-type-label"
          >
            {(['resistance', 'timed_hold', 'distance', 'interval'] as ExerciseType[]).map(t => (
              <button
                key={t}
                onClick={() => setType(t)}
                className={`px-3 py-2.5 rounded-lg text-sm font-semibold border text-left ${
                  type === t
                    ? 'border-transparent text-white'
                    : 'border-border-strong text-text-secondary'
                }`}
                style={
                  type === t
                    ? {
                        borderColor: 'var(--color-primary)',
                        color: 'white',
                        background: 'color-mix(in srgb, var(--color-primary) 8%, transparent)',
                      }
                    : undefined
                }
              >
                {TYPE_LABELS[t]}
              </button>
            ))}
          </div>
        </div>
        <div>
          <label
            htmlFor="exercise-equipment"
            className="label-caps text-text-secondary block mb-1.5"
          >
            Equipment
          </label>
          <select
            id="exercise-equipment"
            value={equip}
            onChange={e => setEquip(e.target.value)}
            className="ps-input w-full px-3 py-2.5 text-sm"
          >
            <option value="">None (bodyweight)</option>
            {equipment.map(eq => (
              <option key={eq.id} value={eq.id}>
                {eq.name}
              </option>
            ))}
          </select>
        </div>
        {type === 'resistance' && (
          <div className="ps-metric p-3 flex flex-col gap-3">
            {/* eslint-disable-next-line jsx-a11y/label-has-associated-control */}
            <label className="flex items-center justify-between" aria-label="Bodyweight base">
              <span className="text-sm">Bodyweight base</span>
              <Toggle checked={bodyweight} onChange={setBodyweight} label="Bodyweight base" />
            </label>
            {/* eslint-disable-next-line jsx-a11y/label-has-associated-control */}
            <label className="flex items-center justify-between" aria-label="Allows added weight">
              <span className="text-sm">Allows added weight</span>
              <Toggle checked={addedWeight} onChange={setAddedWeight} label="Allows added weight" />
            </label>
            {/* eslint-disable-next-line jsx-a11y/label-has-associated-control */}
            <label className="flex items-center justify-between" aria-label="Bilateral">
              <span className="text-sm">Bilateral</span>
              <Toggle checked={bilateral} onChange={setBilateral} label="Bilateral" />
            </label>
          </div>
        )}
      </div>
    </Sheet>
  )
}

export function ExercisesPage() {
  const { data: exercises } = useExercises()
  const { data: equipment } = useEquipment()
  const { data: workouts } = useWorkouts()
  const { deleteExercise, toast } = useApp()
  const navigate = useNavigate()
  const [q, setQ] = useState('')
  const [type, setType] = useState('all')
  const [equip, setEquip] = useState('all')
  const [showCreate, setShowCreate] = useState(false)
  const [deleting, setDeleting] = useState<Exercise | null>(null)

  const filtered = exercises.filter(
    e =>
      (type === 'all' || e.type === type) &&
      (equip === 'all' || e.equipmentTypeId === equip) &&
      e.name.toLowerCase().includes(q.toLowerCase())
  )

  return (
    <>
      <PageHeader
        title="Exercises"
        subtitle={`${exercises.length} in your catalog`}
        actions={
          <Button size="sm" icon={<Plus size={16} />} onClick={() => setShowCreate(true)}>
            Create
          </Button>
        }
      />

      <div className="relative mb-3">
        <Search size={18} className="absolute left-3 top-1/2 -translate-y-1/2 text-text-muted" />
        <input
          value={q}
          onChange={e => setQ(e.target.value)}
          placeholder="Search exercises"
          className="ps-input w-full pl-10 pr-3 py-2.5 text-sm"
        />
      </div>

      <div className="mb-3">
        <SegmentedControl size="sm" value={type} onChange={setType} options={TYPES} />
      </div>

      <div className="flex flex-wrap items-center gap-2 mb-4">
        <button
          onClick={() => setEquip('all')}
          className={`shrink-0 px-3 h-8 rounded-full text-[13px] font-semibold border ${
            equip === 'all'
              ? 'text-white border-transparent'
              : 'border-border-strong text-text-secondary'
          }`}
          style={equip === 'all' ? { background: 'var(--color-primary)' } : undefined}
        >
          All equipment
        </button>
        {equipment
          .filter((eq: EquipmentType) => exercises.some(e => e.equipmentTypeId === eq.id))
          .map((eq: EquipmentType) => (
            <button
              key={eq.id}
              onClick={() => setEquip(eq.id)}
              className={`shrink-0 px-3 h-8 rounded-full text-[13px] font-semibold border ${
                equip === eq.id
                  ? 'text-white border-transparent'
                  : 'border-border-strong text-text-secondary'
              }`}
              style={equip === eq.id ? { background: 'var(--color-primary)' } : undefined}
            >
              {eq.name}
            </button>
          ))}
      </div>

      {filtered.length ? (
        <div className="flex flex-col gap-2.5">
          {filtered.map(ex => {
            const usage = exerciseUsageCount(workouts, ex.id)
            const inUse = usage > 0
            return (
              <Card
                key={ex.id}
                className="p-4 flex items-center gap-3 cursor-pointer"
                onClick={() => navigate(`/exercises/${ex.id}`)}
              >
                <div className="min-w-0 flex-1">
                  <div className="font-semibold text-sm truncate">{ex.name}</div>
                  <div className="text-[12px] text-text-secondary truncate">
                    {equipmentName(equipment, ex.equipmentTypeId)}
                  </div>
                </div>
                <TypeBadge type={ex.type} />
                <button
                  onClick={e => {
                    e.stopPropagation()
                    if (!inUse) setDeleting(ex)
                  }}
                  disabled={inUse}
                  title={
                    inUse
                      ? `${ex.name} is in use by ${usage} workout${usage === 1 ? '' : 's'} and cannot be deleted.`
                      : 'Delete exercise'
                  }
                  aria-label={
                    inUse ? `${ex.name} in use by ${usage} workouts` : `Delete ${ex.name}`
                  }
                  className={`h-10 w-10 flex items-center justify-center rounded-lg shrink-0 ${
                    inUse
                      ? 'text-text-muted opacity-50 cursor-not-allowed'
                      : 'text-text-muted hover:text-destructive hover:bg-surface-muted'
                  }`}
                >
                  <Trash2 size={17} />
                </button>
              </Card>
            )
          })}
        </div>
      ) : (
        <EmptyState
          icon="list"
          title="No exercises found"
          action={
            <Button
              onClick={() => {
                setQ('')
                setType('all')
                setEquip('all')
              }}
              variant="secondary"
            >
              Clear filters
            </Button>
          }
        >
          Try a different search or filter, or create a new exercise.
        </EmptyState>
      )}

      <CreateExerciseSheet open={showCreate} onClose={() => setShowCreate(false)} />
      <ConfirmDialog
        open={!!deleting}
        title="Delete exercise?"
        message={deleting ? `"${deleting.name}" will be removed from your catalog.` : ''}
        onCancel={() => setDeleting(null)}
        onConfirm={() => {
          if (deleting) {
            deleteExercise(deleting.id)
            setDeleting(null)
            toast('Exercise deleted.')
          }
        }}
      />
    </>
  )
}
