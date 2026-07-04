import { useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { isAxiosError } from 'axios'
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
import { useApp } from '@/lib/use-app'
import { equipmentName } from '@/lib/domain'
import { assertNever } from '@/lib/utils'
import { EXERCISE_TYPES, TYPE_OPTIONS } from '@/lib/exercise-types'
import { listExercises, createExercise, deleteExercise as deleteExerciseApi } from '@/api/exercises'
import { listEquipment } from '@/api/equipment'
import type { Exercise, ExerciseType, EquipmentType } from '@/api/types'
import type { CreateExercisePayload } from '@/api/exercises'

const TYPES = [{ value: 'all', label: 'All' }, ...TYPE_OPTIONS]

function CreateExerciseSheet({ onClose }: { onClose: () => void }) {
  const navigate = useNavigate()
  const queryClient = useQueryClient()
  const { toast } = useApp()

  const { data: equipment = [] } = useQuery({
    queryKey: ['equipment'],
    queryFn: listEquipment,
  })

  const [name, setName] = useState('')
  const [notes, setNotes] = useState('')
  const [type, setType] = useState<ExerciseType>('resistance')
  const [equip, setEquip] = useState('')
  const [bodyweight, setBodyweight] = useState(false)
  const [addedWeight, setAddedWeight] = useState(true)
  const [bilateral, setBilateral] = useState(true)
  const [targetDurationSeconds, setTargetDurationSeconds] = useState('')
  const [defaultWorkSeconds, setDefaultWorkSeconds] = useState('')
  const [defaultRestSeconds, setDefaultRestSeconds] = useState('')
  const [defaultRounds, setDefaultRounds] = useState('')
  const [errors, setErrors] = useState<Record<string, string>>({})

  const createMutation = useMutation({
    mutationFn: createExercise,
    onSuccess: exercise => {
      queryClient.invalidateQueries({ queryKey: ['exercises'] })
      toast('Exercise created.')
      onClose()
      navigate(`/exercises/${exercise.id}`)
    },
    onError: error => {
      if (isAxiosError(error) && error.response?.status === 422) {
        const fieldErrors = error.response.data?.errors as Record<string, string[]> | undefined
        if (fieldErrors) {
          const mapped: Record<string, string> = {}
          for (const [key, messages] of Object.entries(fieldErrors)) {
            if (messages[0]) mapped[key] = messages[0]
          }
          setErrors(mapped)
          return
        }
      }
      toast('Could not create exercise. Try again.', 'error')
    },
  })

  const create = () => {
    const trimmed = name.trim()
    if (!trimmed) return

    setErrors({})

    const typeAttributes: Record<string, unknown> = {}

    switch (type) {
      case 'resistance':
        typeAttributes.bodyweight_base = bodyweight
        typeAttributes.allows_added_weight = addedWeight
        typeAttributes.bilateral = bilateral
        break
      case 'timed_hold':
        if (targetDurationSeconds) {
          typeAttributes.target_duration_seconds = parseInt(targetDurationSeconds)
        }
        break
      case 'distance':
        break
      case 'interval':
        if (defaultWorkSeconds) {
          typeAttributes.default_work_seconds = parseInt(defaultWorkSeconds)
        }
        if (defaultRestSeconds) {
          typeAttributes.default_rest_seconds = parseInt(defaultRestSeconds)
        }
        if (defaultRounds) {
          typeAttributes.default_rounds = parseInt(defaultRounds)
        }
        break
      default:
        assertNever(type)
    }

    const payload: CreateExercisePayload = {
      name: trimmed,
      type,
      equipment_type_id: equip ? Number(equip) : null,
      notes: notes.trim() || null,
      type_attributes: typeAttributes,
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
                Target duration (seconds, optional)
              </label>
              <input
                id="target-duration"
                type="number"
                value={targetDurationSeconds}
                onChange={e => setTargetDurationSeconds(e.target.value)}
                className="ps-input w-20 px-2 py-1.5 text-sm"
                min="1"
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
                Work seconds (optional)
              </label>
              <input
                id="default-work"
                type="number"
                value={defaultWorkSeconds}
                onChange={e => setDefaultWorkSeconds(e.target.value)}
                className="ps-input w-20 px-2 py-1.5 text-sm"
                min="1"
              />
            </div>
            <div className="flex items-center justify-between">
              <label htmlFor="default-rest" className="text-sm">
                Rest seconds (optional)
              </label>
              <input
                id="default-rest"
                type="number"
                value={defaultRestSeconds}
                onChange={e => setDefaultRestSeconds(e.target.value)}
                className="ps-input w-20 px-2 py-1.5 text-sm"
                min="1"
              />
            </div>
            <div className="flex items-center justify-between">
              <label htmlFor="default-rounds" className="text-sm">
                Rounds (optional)
              </label>
              <input
                id="default-rounds"
                type="number"
                value={defaultRounds}
                onChange={e => setDefaultRounds(e.target.value)}
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
          <label htmlFor="exercise-name" className="label-caps text-text-secondary block mb-1.5">
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
          <label htmlFor="exercise-notes" className="label-caps text-text-secondary block mb-1.5">
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
                  type === t ? 'border-transparent' : 'border-border-strong text-text-secondary'
                }`}
                style={
                  type === t
                    ? {
                        background: 'var(--color-primary)',
                        color: 'white',
                      }
                    : undefined
                }
              >
                {EXERCISE_TYPES[t].label}
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

        {typeFields()}
      </div>
    </Sheet>
  )
}

export function ExercisesPage() {
  const navigate = useNavigate()
  const queryClient = useQueryClient()
  const { toast } = useApp()

  const { data: exercises = [], isLoading } = useQuery({
    queryKey: ['exercises'],
    queryFn: listExercises,
  })

  const { data: equipment = [] } = useQuery({
    queryKey: ['equipment'],
    queryFn: listEquipment,
  })

  const deleteMutation = useMutation({
    mutationFn: deleteExerciseApi,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['exercises'] })
      toast('Exercise deleted.')
    },
    onError: error => {
      if (isAxiosError(error) && error.response?.status === 409) {
        toast(error.response.data?.message ?? 'Exercise is in use.', 'error')
      } else {
        toast('Could not delete. Try again.', 'error')
      }
    },
  })

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
                          if (!inUse) setDeleting(ex)
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
        open={!!deleting}
        title="Delete exercise?"
        message={deleting ? `"${deleting.name}" will be removed from your catalog.` : ''}
        onCancel={() => setDeleting(null)}
        onConfirm={() => {
          if (deleting) {
            deleteMutation.mutate(deleting.id)
            setDeleting(null)
          }
        }}
      />
    </div>
  )
}
