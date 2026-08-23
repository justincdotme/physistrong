import { useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { Search, Plus, Trash2 } from 'lucide-react'
import { extractFieldErrors, extractConflictMessage } from '@/api/errors'
import { PageHeader } from '@/components/ui/page-header'
import { Button } from '@/components/ui/button'
import { Card } from '@/components/ui/card'
import { TypeBadge } from '@/components/ui/type-badge'
import { SegmentedControl } from '@/components/ui/segmented-control'
import { EmptyState } from '@/components/ui/empty-state'
import { ConfirmDialog } from '@/components/ui/confirm-dialog'
import { Sheet } from '@/components/ui/sheet'
import { Toggle } from '@/components/ui/toggle'
import { useDeleteConfirm } from '@/hooks/use-delete-confirm'
import { useApp } from '@/lib/use-app'
import { equipmentName } from '@/lib/domain'
import { assertNever } from '@/lib/utils'
import { DurationInput } from '@/components/ui/duration-input'
import { TYPE_OPTIONS, buildTypeAttributes } from '@/lib/exercise-types'
import {
  exerciseQueries,
  createExercise,
  deleteExercise as deleteExerciseApi,
} from '@/api/exercises'
import { equipmentQueries } from '@/api/equipment'
import type { Exercise, ExerciseType, EquipmentType } from '@/api/types'
import type { CreateExercisePayload } from '@/api/exercises'

const TYPES = [{ value: 'all', label: 'All' }, ...TYPE_OPTIONS]

function CreateExerciseSheet({ onClose }: { onClose: () => void }) {
  const navigate = useNavigate()
  const queryClient = useQueryClient()
  const { toast } = useApp()

  const { data: equipment = [] } = useQuery(equipmentQueries.list())

  const [name, setName] = useState('')
  const [notes, setNotes] = useState('')
  const [type, setType] = useState<ExerciseType>('resistance')
  const [equip, setEquip] = useState('')
  const [bodyweight, setBodyweight] = useState(false)
  const [addedWeight, setAddedWeight] = useState(true)
  const [bilateral, setBilateral] = useState(true)
  const [targetDurationSeconds, setTargetDurationSeconds] = useState<number | null>(null)
  const [defaultWorkSeconds, setDefaultWorkSeconds] = useState<number | null>(null)
  const [defaultRestSeconds, setDefaultRestSeconds] = useState<number | null>(null)
  const [defaultRounds, setDefaultRounds] = useState<number | null>(null)
  const [errors, setErrors] = useState<Record<string, string>>({})

  const createMutation = useMutation({
    mutationFn: createExercise,
    onSuccess: exercise => {
      queryClient.invalidateQueries({ queryKey: exerciseQueries.base })
      queryClient.invalidateQueries({ queryKey: equipmentQueries.base })
      toast('Exercise created.')
      onClose()
      navigate(`/exercises/${exercise.id}`)
    },
    onError: error => {
      const mapped = extractFieldErrors(error)
      if (Object.keys(mapped).length) {
        setErrors(mapped)
        return
      }
      toast('Could not create exercise. Try again.', 'error')
    },
  })

  const create = () => {
    const trimmed = name.trim()
    if (!trimmed) return

    setErrors({})

    const payload: CreateExercisePayload = {
      name: trimmed,
      type,
      equipment_type_id: equip ? Number(equip) : null,
      notes: notes.trim() || null,
      type_attributes: buildTypeAttributes(type, {
        bodyweight,
        addedWeight,
        bilateral,
        targetDurationSeconds,
        defaultWorkSeconds,
        defaultRestSeconds,
        defaultRounds,
      }),
    }

    createMutation.mutate(payload)
  }

  function typeFields() {
    switch (type) {
      case 'resistance':
        return (
          <div className="ps-metric p-3 flex flex-col gap-3">
            <div className="flex items-center justify-between" aria-label="Bodyweight base">
              <span className="text-sm">Bodyweight base</span>
              <Toggle checked={bodyweight} onChange={setBodyweight} label="Bodyweight base" />
            </div>
            <div className="flex items-center justify-between" aria-label="Allows added weight">
              <span className="text-sm">Allows added weight</span>
              <Toggle checked={addedWeight} onChange={setAddedWeight} label="Allows added weight" />
            </div>
            <div className="flex items-center justify-between" aria-label="Bilateral">
              <span className="text-sm">Bilateral</span>
              <Toggle checked={bilateral} onChange={setBilateral} label="Bilateral" />
            </div>
          </div>
        )
      case 'timed_hold':
        return (
          <div className="ps-metric p-3 flex flex-col gap-3">
            <div className="flex items-center justify-between">
              <label htmlFor="target-duration" className="text-sm">
                Target duration (optional)
              </label>
              <DurationInput
                id="target-duration"
                label="Target duration"
                value={targetDurationSeconds}
                onChange={setTargetDurationSeconds}
                className="ps-input px-2 py-1.5 text-sm"
              />
            </div>
          </div>
        )
      case 'distance':
        return null
      case 'interval':
        return (
          <div className="ps-metric p-3 flex flex-col gap-3">
            <div className="flex items-center justify-between">
              <label htmlFor="default-work" className="text-sm">
                Work (optional)
              </label>
              <DurationInput
                id="default-work"
                label="Work"
                value={defaultWorkSeconds}
                onChange={setDefaultWorkSeconds}
                className="ps-input px-2 py-1.5 text-sm"
              />
            </div>
            <div className="flex items-center justify-between">
              <label htmlFor="default-rest" className="text-sm">
                Rest (optional)
              </label>
              <DurationInput
                id="default-rest"
                label="Rest"
                value={defaultRestSeconds}
                onChange={setDefaultRestSeconds}
                className="ps-input px-2 py-1.5 text-sm"
              />
            </div>
            <div className="flex items-center justify-between">
              <label htmlFor="default-rounds" className="text-sm">
                Rounds (optional)
              </label>
              <input
                id="default-rounds"
                type="number"
                value={defaultRounds ?? ''}
                onChange={e =>
                  setDefaultRounds(e.target.value === '' ? null : Number(e.target.value))
                }
                className="ps-input w-20 px-2 py-1.5 text-sm"
                min="1"
              />
            </div>
          </div>
        )
      default:
        return assertNever(type)
    }
  }

  return (
    <Sheet
      open={true}
      onClose={onClose}
      title="Create Exercise"
      footer={
        <Button full disabled={!name.trim() || createMutation.isPending} onClick={create}>
          {createMutation.isPending ? 'Creating...' : 'Create Exercise'}
        </Button>
      }
    >
      <div className="flex flex-col gap-4">
        <div>
          <label htmlFor="exercise-name" className="form-label">
            Name
          </label>
          <input
            id="exercise-name"
            value={name}
            onChange={e => setName(e.target.value)}
            placeholder="e.g. Incline Bench Press"
            className={`ps-input w-full px-3 py-2.5 text-sm ${errors.name ? 'border-destructive' : ''}`}
          />
          {errors.name && <p className="text-destructive text-xs mt-1">{errors.name}</p>}
        </div>

        <div>
          <label htmlFor="exercise-notes" className="form-label">
            Notes (optional)
          </label>
          <textarea
            id="exercise-notes"
            value={notes}
            onChange={e => setNotes(e.target.value)}
            placeholder="e.g. Variations, form cues..."
            className="ps-input w-full px-3 py-2.5 text-sm resize-none"
            rows={3}
          />
        </div>

        <div>
          {/* eslint-disable-next-line jsx-a11y/label-has-associated-control */}
          <label id="exercise-type-label" className="form-label">
            Type
          </label>
          <div
            className="grid grid-cols-2 gap-2"
            role="group"
            aria-labelledby="exercise-type-label"
          >
            {TYPE_OPTIONS.map(opt => (
              <button
                key={opt.value}
                onClick={() => setType(opt.value)}
                className={`px-3 py-2.5 rounded-lg text-sm font-semibold border text-left ${
                  type === opt.value
                    ? 'border-transparent'
                    : 'border-border-strong text-text-secondary'
                }`}
                style={
                  type === opt.value
                    ? {
                        background: 'var(--color-primary)',
                        color: 'white',
                      }
                    : undefined
                }
              >
                {opt.label}
              </button>
            ))}
          </div>
        </div>

        <div>
          <label htmlFor="exercise-equipment" className="form-label">
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

        {typeFields()}
      </div>
    </Sheet>
  )
}

export function ExercisesPage() {
  const navigate = useNavigate()
  const queryClient = useQueryClient()
  const { toast } = useApp()

  const { data: exercises = [], isLoading } = useQuery(exerciseQueries.list())

  const { data: equipment = [] } = useQuery(equipmentQueries.list())

  const deleteMutation = useMutation({
    mutationFn: deleteExerciseApi,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: exerciseQueries.base })
      queryClient.invalidateQueries({ queryKey: equipmentQueries.base })
      toast('Exercise deleted.')
    },
    onError: error => {
      const conflictMsg = extractConflictMessage(error, 'Exercise is in use.')
      if (conflictMsg) {
        toast(conflictMsg, 'error')
      } else {
        toast('Could not delete. Try again.', 'error')
      }
    },
  })

  const deleteConfirm = useDeleteConfirm<Exercise>(ex => deleteMutation.mutate(ex.id))

  const [q, setQ] = useState('')
  const [type, setType] = useState('all')
  const [equip, setEquip] = useState('all')
  const [showCreate, setShowCreate] = useState(false)

  const filtered = exercises.filter(
    e =>
      (type === 'all' || e.type === type) &&
      (equip === 'all' || e.equipmentTypeId === equip) &&
      e.name.toLowerCase().includes(q.toLowerCase())
  )

  if (isLoading) {
    return (
      <>
        <PageHeader title="Exercises" />
        <p className="text-text-secondary text-sm">Loading...</p>
      </>
    )
  }

  return (
    <div dusk="exercises-page">
      <PageHeader
        title="Exercises"
        subtitle={`${exercises.length} in your catalog`}
        actions={
          <Button
            size="sm"
            icon={<Plus size={16} />}
            onClick={() => setShowCreate(true)}
            dusk="create-exercise-btn"
          >
            Create
          </Button>
        }
      />

      <div className="relative mb-3">
        <Search size={18} className="absolute left-3 top-1/2 -translate-y-1/2 text-text-muted" />
        <input
          dusk="exercise-search"
          value={q}
          onChange={e => setQ(e.target.value)}
          placeholder="Search exercises"
          className="ps-input w-full pl-10 pr-3 py-2.5 text-sm"
        />
      </div>

      <div className="mb-3" dusk="type-filter">
        <SegmentedControl size="sm" value={type} onChange={setType} options={TYPES} />
      </div>

      <div className="flex flex-wrap items-center gap-2 mb-4" dusk="equipment-filter">
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
        <div className="flex flex-col gap-2.5" dusk="exercise-list">
          {filtered.map(ex => {
            const isSystem = ex.userId === null
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
                {!isSystem &&
                  (() => {
                    const inUse = ex.usageCount > 0 || ex.hasLoggedData
                    return (
                      <button
                        onClick={e => {
                          e.stopPropagation()
                          if (!inUse) deleteConfirm.request(ex)
                        }}
                        disabled={inUse}
                        title={
                          inUse
                            ? ex.usageCount > 0
                              ? `In use by ${ex.usageCount} workout(s)`
                              : 'Has logged workout data'
                            : 'Delete exercise'
                        }
                        aria-label={inUse ? `${ex.name} is in use` : `Delete ${ex.name}`}
                        className={`h-10 w-10 flex items-center justify-center rounded-lg shrink-0 ${
                          inUse
                            ? 'text-text-muted opacity-50 cursor-not-allowed'
                            : 'text-text-muted hover:text-destructive hover:bg-surface-muted'
                        }`}
                      >
                        <Trash2 size={17} />
                      </button>
                    )
                  })()}
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

      {showCreate && <CreateExerciseSheet onClose={() => setShowCreate(false)} />}
      <ConfirmDialog
        {...deleteConfirm.dialogProps}
        title="Delete exercise?"
        message={
          deleteConfirm.target
            ? `"${deleteConfirm.target.name}" will be removed from your catalog.`
            : ''
        }
      />
    </div>
  )
}
