import { useState, useRef, useCallback, useEffect } from 'react'
import { useParams, useNavigate } from 'react-router-dom'
import { useQuery, useMutation, useQueryClient, useQueries } from '@tanstack/react-query'
import { Trash2, Flame, Target, Plus, Calendar, Route, Check } from 'lucide-react'
import type { WorkoutEntry, EntryGroup, Exercise, EquipmentType, Workout } from '@/api/types'
import { exerciseQueries } from '@/api/exercises'
import { equipmentQueries } from '@/api/equipment'
import {
  workoutQueries,
  updateWorkout as updateWorkoutApi,
  deleteWorkout as deleteWorkoutApi,
  attachExercise,
  detachExercise,
  reorderExercises,
  createEntry,
  updateEntry,
  deleteEntry,
  reorderEntries,
  createGroup,
  deleteGroup,
  assignEntries,
} from '@/api/workouts'
import { toMetricsPayload } from '@/api/transformers'
import type { CreateEntryPayload, AssignEntryPayload } from '@/api/workouts'
import { extractAllTimeBest } from '@/api/progress'
import { EXERCISE_TYPES } from '@/lib/exercise-types'
import { useApp } from '@/lib/use-app'
import { useAuth } from '@/hooks/use-auth'
import { formatDuration } from '@/lib/formatters'
import { workoutCompletion, exerciseById, equipmentName, groupRoundProgress } from '@/lib/domain'
import { PageHeader } from '@/components/ui/page-header'
import { Button } from '@/components/ui/button'
import { Card } from '@/components/ui/card'
import { TypeBadge } from '@/components/ui/type-badge'
import { CompletionBar } from '@/components/ui/completion-bar'
import { InlineEdit } from '@/components/ui/inline-edit'
import { ConfirmDialog } from '@/components/ui/confirm-dialog'
import { RatingSlider } from '@/components/ui/rating-slider'
import { ReorderList } from '@/components/app/reorderable'
import { EntryMetrics } from '@/components/app/metric-inputs'
import { ExercisePicker, GroupConfigSheet } from '@/components/app/pickers'
import { GroupSelectBanner } from '@/components/app/group-select-banner'
import { collectReorderExerciseIds } from './workout-detail.utils'

interface Block {
  kind: 'exercise' | 'group'
  id: string
  exerciseId?: string
  entries: WorkoutEntry[]
  gid?: string
  group?: EntryGroup
}

function buildBlocks(entries: WorkoutEntry[], groups: EntryGroup[]): Block[] {
  const sorted = [...entries].sort((a, b) => a.setOrder - b.setOrder)
  const blocks: Block[] = []
  let i = 0

  while (i < sorted.length) {
    const e = sorted[i]
    if (!e) break
    if (e.entryGroupId) {
      const gid = e.entryGroupId
      const group = groups.find(g => g.id === gid)
      const es: WorkoutEntry[] = []
      while (i < sorted.length) {
        const entry = sorted[i]
        if (!entry || entry.entryGroupId !== gid) break
        es.push(entry)
        i++
      }
      blocks.push({ kind: 'group', id: `b-${gid}`, gid, group, entries: es })
    } else {
      const exId = e.exerciseId
      const es: WorkoutEntry[] = []
      while (i < sorted.length) {
        const entry = sorted[i]
        if (!entry || entry.entryGroupId || entry.exerciseId !== exId) break
        es.push(entry)
        i++
      }
      blocks.push({
        kind: 'exercise',
        id: `b-${exId}-${es[0]?.id || 'new'}`,
        exerciseId: exId,
        entries: es,
      })
    }
  }

  return blocks
}

function defaultEntryPayload(ex: Exercise, setOrder: number): CreateEntryPayload {
  return {
    exercise_id: Number(ex.id),
    set_order: setOrder,
    metrics: EXERCISE_TYPES[ex.type].defaultEntryMetrics(ex),
  }
}

function ExerciseHeader({
  exercise,
  handle,
  controls,
  equipmentList,
}: {
  exercise: Exercise
  handle: React.ReactNode
  controls: React.ReactNode
  equipmentList: EquipmentType[]
}) {
  return (
    <div className="flex items-center gap-2 mb-3">
      {handle}
      <div className="min-w-0 flex-1">
        <div className="font-semibold text-base truncate">{exercise.name}</div>
        <div className="text-[12px] text-text-secondary">
          {equipmentName(equipmentList, exercise.equipmentTypeId)}
        </div>
      </div>
      <TypeBadge type={exercise.type} />
      {controls}
    </div>
  )
}

interface SetRowProps {
  entry: WorkoutEntry
  exercise: Exercise
  index: number
  handle: React.ReactNode
  controls: React.ReactNode
  onPatch: (patch: Partial<WorkoutEntry>) => void
  onRemove: () => void
  allTimeBest: number | null
}

function SetRow({
  entry,
  exercise,
  index,
  handle,
  controls,
  onPatch,
  onRemove,
  allTimeBest,
}: SetRowProps) {
  return (
    <div dusk="entry-row" className="flex items-start gap-2">
      <div className="flex flex-col items-center shrink-0">
        <span className="label-caps text-text-muted mb-0.5">{index + 1}</span>
        {handle}
        {controls}
      </div>
      <div className="flex-1 min-w-0 pt-1">
        <EntryMetrics
          entry={entry}
          exercise={exercise}
          allTimeBest={allTimeBest}
          onChange={onPatch}
        />
      </div>
      <button
        onClick={onRemove}
        aria-label="Remove set"
        title="Remove set"
        className="h-11 w-11 flex items-center justify-center rounded-lg text-text-muted hover:text-destructive hover:bg-surface-muted shrink-0"
      >
        <Trash2 size={18} />
      </button>
    </div>
  )
}

function GroupBlockCard({
  block,
  exercises,
  allTimeBestMap,
  handle,
  controls,
  onPatchEntry,
  onUngroup,
}: {
  block: Block
  exercises: Exercise[]
  allTimeBestMap: Map<string, number | null>
  handle: React.ReactNode
  controls: React.ReactNode
  onPatchEntry: (entryId: string, patch: Partial<WorkoutEntry>) => void
  onUngroup: (groupId: string) => void
}) {
  const g = block.group
  const { rounds, completedRounds, displayRound } = groupRoundProgress(
    block.entries,
    g?.plannedRounds
  )

  return (
    <div
      dusk="entry-group"
      className="pl-3 ml-1"
      style={{ borderLeft: '2px solid var(--color-primary)' }}
    >
      <Card className="p-4">
        <div className="flex items-center gap-2 mb-3">
          {handle}
          <div className="min-w-0 flex-1">
            <div className="flex items-center gap-2">
              <span className="font-semibold text-base truncate">{g?.name || 'Superset'}</span>
              <span
                className="label-caps px-1.5 py-0.5 rounded-full"
                style={{
                  color: 'var(--color-primary)',
                  background: 'color-mix(in srgb, var(--color-primary) 12%, transparent)',
                }}
              >
                Round {displayRound} of {rounds}
              </span>
            </div>
            <div className="text-[12px] text-text-secondary">
              {formatDuration(g?.restBetweenExercisesSeconds || 0)} between ·{' '}
              {g?.restBetweenRoundsSeconds
                ? `${formatDuration(g.restBetweenRoundsSeconds)} / round`
                : 'no round rest'}
            </div>
          </div>
          {block.gid && (
            <button
              onClick={() => onUngroup(block.gid as string)}
              className="text-[12px] font-semibold text-text-secondary hover:text-destructive px-2 h-9 rounded-lg hover:bg-surface-muted"
            >
              Ungroup
            </button>
          )}
          {controls}
        </div>
        <div className="flex flex-col gap-4">
          {Array.from({ length: rounds }, (_, ri) => ri + 1).map(r => (
            <div key={r}>
              <div className="flex items-center gap-2 mb-2">
                <span className="label-caps text-text-muted">Round {r}</span>
                <span className="h-px flex-1" style={{ background: 'var(--color-border)' }} />
                {completedRounds.includes(r) && (
                  <Check size={14} style={{ color: 'var(--color-success)' }} />
                )}
              </div>
              <div className="flex flex-col gap-3">
                {block.entries
                  .filter(e => e.groupRound === r)
                  .map(entry => {
                    const ex = exerciseById(exercises, entry.exerciseId)
                    if (!ex) return null
                    return (
                      <div key={entry.id}>
                        <div className="text-[13px] font-semibold mb-1.5">{ex.name}</div>
                        <EntryMetrics
                          entry={entry}
                          exercise={ex}
                          allTimeBest={allTimeBestMap.get(ex.id) ?? null}
                          onChange={p => onPatchEntry(entry.id, p)}
                        />
                      </div>
                    )
                  })}
              </div>
            </div>
          ))}
        </div>
      </Card>
    </div>
  )
}

function SelectableExerciseCard({
  exercise,
  equipmentList,
  isSelected,
  onToggle,
}: {
  exercise: Exercise
  equipmentList: EquipmentType[]
  isSelected: boolean
  onToggle: () => void
}) {
  return (
    <Card
      dusk="exercise-section"
      className="p-4"
      style={
        isSelected
          ? { outline: '2px solid var(--color-primary)', outlineOffset: '-1px' }
          : undefined
      }
    >
      <button
        onClick={onToggle}
        className="flex items-center gap-3 w-full text-left cursor-pointer"
      >
        <span
          className="h-6 w-6 rounded-md border-2 flex items-center justify-center shrink-0"
          style={
            isSelected
              ? {
                  background: 'var(--color-primary)',
                  borderColor: 'var(--color-primary)',
                  color: '#fff',
                }
              : { borderColor: 'var(--color-border-strong)' }
          }
        >
          {isSelected && <Check size={16} />}
        </span>
        <div className="min-w-0 flex-1">
          <div className="font-semibold text-base truncate">{exercise.name}</div>
          <div className="text-[12px] text-text-secondary">
            {equipmentName(equipmentList, exercise.equipmentTypeId)}
          </div>
        </div>
        <TypeBadge type={exercise.type} />
      </button>
    </Card>
  )
}

function ExerciseBlockCard({
  block,
  exercise,
  equipmentList,
  allTimeBestMap,
  handle,
  controls,
  onReorderSets,
  onPatchEntry,
  onRemoveSet,
  onAddSet,
  onRemoveExercise,
}: {
  block: Block
  exercise: Exercise
  equipmentList: EquipmentType[]
  allTimeBestMap: Map<string, number | null>
  handle: React.ReactNode
  controls: React.ReactNode
  onReorderSets: (nextEntries: WorkoutEntry[]) => void
  onPatchEntry: (entryId: string, patch: Partial<WorkoutEntry>) => void
  onRemoveSet: (entryId: string) => void
  onAddSet: () => void
  onRemoveExercise: () => void
}) {
  return (
    <Card dusk="exercise-section" className="p-4">
      <ExerciseHeader
        exercise={exercise}
        handle={handle}
        controls={
          <div className="flex items-center gap-1">
            {controls}
            <button
              onClick={onRemoveExercise}
              aria-label="Remove exercise"
              title="Remove exercise"
              className="h-10 w-10 flex items-center justify-center rounded-lg text-text-muted hover:text-destructive hover:bg-surface-muted"
            >
              <Trash2 size={17} />
            </button>
          </div>
        }
        equipmentList={equipmentList}
      />
      <ReorderList
        items={block.entries}
        getKey={e => e.id}
        onReorder={onReorderSets}
        className="flex flex-col gap-3"
        itemClassName="rounded-lg"
        renderItem={(entry, { index, handle: h, controls: c }) => (
          <SetRow
            entry={entry}
            exercise={exercise}
            index={index}
            handle={h}
            controls={c}
            onPatch={p => onPatchEntry(entry.id, p)}
            onRemove={() => onRemoveSet(entry.id)}
            allTimeBest={allTimeBestMap.get(exercise.id) ?? null}
          />
        )}
      />
      <Button
        variant="secondary"
        size="sm"
        icon={<Plus size={16} />}
        full
        className="mt-3"
        onClick={onAddSet}
      >
        Add Set
      </Button>
    </Card>
  )
}

export function WorkoutDetailPage() {
  const { id: workoutId = '' } = useParams<{ id: string }>()
  const navigate = useNavigate()
  const queryClient = useQueryClient()
  const { toast } = useApp()
  const { user } = useAuth()

  const { data: workout, isLoading } = useQuery({
    ...workoutQueries.detail(workoutId),
    enabled: !!workoutId,
  })

  const { data: exercises = [] } = useQuery(exerciseQueries.list())

  const { data: equipment = [] } = useQuery(equipmentQueries.list())

  const exerciseIdsInWorkout = workout ? [...new Set(workout.entries.map(e => e.exerciseId))] : []

  const recordsQueries = useQueries({
    queries: exerciseIdsInWorkout.map(exId => ({
      ...exerciseQueries.records(exId),
      enabled: !!workout,
      staleTime: 1000 * 60 * 5,
    })),
  })

  // useQueries preserves input order, so result i belongs to exerciseIdsInWorkout[i].
  const allTimeBestMap = new Map<string, number | null>()
  recordsQueries.forEach((q, i) => {
    const exId = exerciseIdsInWorkout[i]
    if (q.data && exId) {
      const ex = exerciseById(exercises, exId)
      if (ex) {
        allTimeBestMap.set(exId, extractAllTimeBest(q.data, ex.type))
      }
    }
  })

  const [exercisePickerOpen, setExercisePickerOpen] = useState(false)
  const [confirmDeleteOpen, setConfirmDeleteOpen] = useState(false)
  const [confirmRemoveExerciseId, setConfirmRemoveExerciseId] = useState<string | null>(null)
  const [selectMode, setSelectMode] = useState(false)
  const [selected, setSelected] = useState<string[]>([])
  const [groupSheetOpen, setGroupSheetOpen] = useState(false)

  const pendingUpdates = useRef(
    new Map<string, { timer: ReturnType<typeof setTimeout>; entry: WorkoutEntry }>()
  )

  const invalidateWorkout = useCallback(() => {
    queryClient.invalidateQueries({ queryKey: workoutQueries.detail(workoutId).queryKey })
  }, [queryClient, workoutId])

  const invalidateWorkoutList = useCallback(() => {
    queryClient.invalidateQueries({ queryKey: workoutQueries.lists })
  }, [queryClient])

  const updateWorkoutMutation = useMutation({
    mutationFn: (payload: Parameters<typeof updateWorkoutApi>[1]) =>
      updateWorkoutApi(workoutId, payload),
    onSuccess: data => {
      queryClient.setQueryData(workoutQueries.detail(workoutId).queryKey, data)
      invalidateWorkoutList()
    },
    onError: () => toast('Could not save. Try again.', 'error'),
  })

  const deleteWorkoutMutation = useMutation({
    mutationFn: () => deleteWorkoutApi(workoutId),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: workoutQueries.base })
      toast('Workout deleted.')
      navigate('/workouts')
    },
    onError: () => toast('Could not delete. Try again.', 'error'),
  })

  const attachExerciseMutation = useMutation({
    mutationFn: async (ex: Exercise) => {
      await attachExercise(workoutId, ex.id)
      const nextOrder = workout ? workout.entries.length : 0
      await createEntry(workoutId, defaultEntryPayload(ex, nextOrder))
    },
    onSuccess: () => {
      invalidateWorkout()
      invalidateWorkoutList()
      queryClient.invalidateQueries({ queryKey: exerciseQueries.base })
      toast('Exercise added.')
    },
    onError: () => toast('Could not add exercise. Try again.', 'error'),
  })

  const detachExerciseMutation = useMutation({
    mutationFn: async (exerciseId: string) => {
      await flushPendingUpdates()
      return detachExercise(workoutId, exerciseId)
    },
    onSuccess: () => {
      invalidateWorkout()
      invalidateWorkoutList()
      queryClient.invalidateQueries({ queryKey: exerciseQueries.base })
      toast('Exercise removed.')
    },
    onError: () => toast('Could not remove exercise. Try again.', 'error'),
  })

  const reorderExercisesMutation = useMutation({
    mutationFn: (ids: string[]) => reorderExercises(workoutId, ids),
    onSuccess: data => {
      queryClient.setQueryData(workoutQueries.detail(workoutId).queryKey, data)
      invalidateWorkoutList()
    },
    onError: () => {
      toast('Could not reorder exercises. Try again.', 'error')
      invalidateWorkout()
    },
  })

  const addSetMutation = useMutation({
    mutationFn: (payload: CreateEntryPayload) => createEntry(workoutId, payload),
    onSuccess: () => {
      invalidateWorkout()
      invalidateWorkoutList()
    },
    onError: () => toast('Could not add set. Try again.', 'error'),
  })

  const updateEntryMutation = useMutation({
    mutationFn: ({
      entryId,
      payload,
    }: {
      entryId: string
      payload: Parameters<typeof updateEntry>[2]
    }) => updateEntry(workoutId, entryId, payload),
    onSuccess: updatedEntry => {
      // A newer local edit is pending; skip this stale server response.
      if (pendingUpdates.current.has(updatedEntry.id)) return
      queryClient.setQueryData(
        workoutQueries.detail(workoutId).queryKey,
        (old: Workout | undefined) => {
          if (!old) return old
          return {
            ...old,
            entries: old.entries.map(e => (e.id === updatedEntry.id ? updatedEntry : e)),
          }
        }
      )
      invalidateWorkoutList()
    },
    onError: () => {
      toast('Could not save. Try again.', 'error')
      invalidateWorkout()
    },
  })

  const flushPendingUpdates = useCallback(() => {
    const pending = pendingUpdates.current
    const flushes = Array.from(pending.values()).map(({ timer, entry }) => {
      clearTimeout(timer)
      return updateEntryMutation.mutateAsync({
        entryId: entry.id,
        payload: { metrics: toMetricsPayload(entry) },
      })
    })
    pending.clear()
    return Promise.allSettled(flushes)
  }, [updateEntryMutation])

  const flushRef = useRef(flushPendingUpdates)
  useEffect(() => {
    flushRef.current = flushPendingUpdates
  })

  useEffect(() => {
    return () => {
      void flushRef.current()
    }
  }, [])

  const deleteEntryMutation = useMutation({
    mutationFn: async (entryId: string) => {
      await flushPendingUpdates()
      return deleteEntry(workoutId, entryId)
    },
    onSuccess: () => {
      invalidateWorkout()
      invalidateWorkoutList()
    },
    onError: () => toast('Could not remove set. Try again.', 'error'),
  })

  const reorderEntriesMutation = useMutation({
    mutationFn: (ids: string[]) => reorderEntries(workoutId, ids),
    onSuccess: invalidateWorkout,
    onError: () => {
      toast('Could not reorder sets. Try again.', 'error')
      invalidateWorkout()
    },
  })

  const createGroupMutation = useMutation({
    mutationFn: async (config: {
      name: string | null
      plannedRounds: number
      restBetweenExercisesSeconds: number
      restBetweenRoundsSeconds: number
    }) => {
      if (!workout) throw new Error('Workout not loaded')

      const afterCreate = await createGroup(workoutId, {
        name: config.name,
        planned_rounds: config.plannedRounds,
        rest_between_exercises_seconds: config.restBetweenExercisesSeconds,
        rest_between_rounds_seconds: config.restBetweenRoundsSeconds,
      })

      const existingIds = new Set((workout?.entryGroups ?? []).map(g => g.id))
      const newGroup = afterCreate.entryGroups.find(g => !existingIds.has(g.id))
      if (!newGroup) throw new Error('Group not created')

      const currentBlocks = buildBlocks(workout?.entries ?? [], workout?.entryGroups ?? [])
      const selectedBlocks = currentBlocks.filter(b => selected.includes(b.id))
      const assignments: AssignEntryPayload[] = selectedBlocks.flatMap(b =>
        b.entries.map(e => ({ entry_id: Number(e.id), group_round: 1 }))
      )

      return assignEntries(workoutId, newGroup.id, assignments)
    },
    onSuccess: data => {
      queryClient.setQueryData(workoutQueries.detail(workoutId).queryKey, data)
      setSelectMode(false)
      setSelected([])
      toast('Group created.')
    },
    onError: () => toast('Could not create group. Try again.', 'error'),
  })

  const ungroupMutation = useMutation({
    mutationFn: (groupId: string) => deleteGroup(workoutId, groupId),
    onSuccess: () => {
      invalidateWorkout()
      toast('Group removed.')
    },
    onError: () => toast('Could not ungroup. Try again.', 'error'),
  })

  const debouncedEntryUpdate = useCallback(
    (entryId: string, entry: WorkoutEntry) => {
      const pending = pendingUpdates.current
      const existing = pending.get(entryId)
      if (existing) clearTimeout(existing.timer)

      const timer = setTimeout(() => {
        pending.delete(entryId)
        updateEntryMutation.mutate({
          entryId,
          payload: { metrics: toMetricsPayload(entry) },
        })
      }, 800)
      pending.set(entryId, { timer, entry })
    },
    [updateEntryMutation]
  )

  const patchEntry = useCallback(
    (entryId: string, patch: Partial<WorkoutEntry>) => {
      const detailKey = workoutQueries.detail(workoutId).queryKey
      queryClient.cancelQueries({ queryKey: detailKey })
      const oldWorkout = queryClient.getQueryData<Workout>(detailKey)
      if (!oldWorkout) return

      const updatedEntries = oldWorkout.entries.map(e =>
        e.id === entryId ? { ...e, ...patch } : e
      )
      queryClient.setQueryData(detailKey, {
        ...oldWorkout,
        entries: updatedEntries,
      })

      const merged = updatedEntries.find(e => e.id === entryId)
      if (merged) {
        debouncedEntryUpdate(entryId, merged)
      }
    },
    [queryClient, workoutId, debouncedEntryUpdate]
  )

  if (!workoutId) {
    return (
      <div className="py-16 text-center text-text-muted">
        Workout not found.{' '}
        <button className="text-primary font-semibold" onClick={() => navigate('/workouts')}>
          Back to Workouts
        </button>
      </div>
    )
  }

  if (isLoading || !user) {
    return (
      <>
        <PageHeader back onBack={() => navigate('/workouts')} title="Loading..." />
        <p className="text-text-secondary text-sm">Loading workout...</p>
      </>
    )
  }

  if (!workout) {
    return (
      <div className="py-16 text-center text-text-muted">
        Workout not found.{' '}
        <button className="text-primary font-semibold" onClick={() => navigate('/workouts')}>
          Back to Workouts
        </button>
      </div>
    )
  }

  const blocks = buildBlocks(workout.entries, workout.entryGroups)
  const ratio = workoutCompletion(workout)

  const handleReorderBlocks = (next: Block[]) => {
    const allEntries = next.flatMap(b => b.entries)
    const ids = allEntries.map(e => e.id)

    queryClient.setQueryData(
      workoutQueries.detail(workoutId).queryKey,
      (old: Workout | undefined) => {
        if (!old) return old
        return {
          ...old,
          entries: allEntries.map((e, idx) => ({ ...e, setOrder: idx })),
        }
      }
    )

    const exerciseIds = collectReorderExerciseIds(next, workout.exercises)
    if (exerciseIds.length > 1) {
      reorderExercisesMutation.mutate(exerciseIds)
    }
    reorderEntriesMutation.mutate(ids)
  }

  const handleReorderSets = (blockId: string, nextEntries: WorkoutEntry[]) => {
    const newBlocks = blocks.map(b => (b.id === blockId ? { ...b, entries: nextEntries } : b))
    const allEntries = newBlocks.flatMap(b => b.entries)
    const ids = allEntries.map(e => e.id)

    queryClient.setQueryData(
      workoutQueries.detail(workoutId).queryKey,
      (old: Workout | undefined) => {
        if (!old) return old
        return {
          ...old,
          entries: allEntries.map((e, idx) => ({ ...e, setOrder: idx })),
        }
      }
    )

    reorderEntriesMutation.mutate(ids)
  }

  const handleAddSet = (block: Block) => {
    const ex = exerciseById(exercises, block.exerciseId ?? '')
    if (!ex) return
    const nextOrder = workout.entries.length
    addSetMutation.mutate(defaultEntryPayload(ex, nextOrder))
  }

  const handleRemoveSet = (entryId: string) => {
    deleteEntryMutation.mutate(entryId)
  }

  const handleAddExercise = (ex: Exercise) => {
    attachExerciseMutation.mutate(ex)
  }

  const toggleSelect = (blockId: string) =>
    setSelected(s => (s.includes(blockId) ? s.filter(x => x !== blockId) : [...s, blockId]))

  const exerciseBlockCount = blocks.filter(b => b.kind === 'exercise').length

  return (
    <div dusk="workout-detail-page">
      <PageHeader
        back
        onBack={() => navigate('/workouts')}
        title={
          <InlineEdit
            value={workout.name}
            onChange={v => updateWorkoutMutation.mutate({ name: v })}
            ariaLabel="Workout name"
            duskDataAttribute="workout-name"
          />
        }
        actions={
          <button
            onClick={() => setConfirmDeleteOpen(true)}
            aria-label="Delete workout"
            title="Delete workout"
            className="h-11 w-11 flex items-center justify-center rounded-lg text-text-muted hover:text-destructive hover:bg-surface-muted"
          >
            <Trash2 size={19} />
          </button>
        }
      />

      <div className="ps-card p-4 mb-5">
        <div className="flex items-center justify-between mb-3">
          <label className="flex items-center gap-2 text-sm text-text-secondary">
            <Calendar size={16} />
            <input
              type="date"
              value={workout.date}
              onChange={e => updateWorkoutMutation.mutate({ date: e.target.value })}
              className="bg-transparent font-medium text-text-primary outline-none"
              aria-label="Workout date"
            />
          </label>
          <span
            className="text-sm font-semibold"
            style={{
              color: ratio >= 1 ? 'var(--color-success)' : 'var(--color-text-secondary)',
            }}
          >
            {Math.round(ratio * 100)}% complete
          </span>
        </div>
        <CompletionBar ratio={ratio} height={6} />
      </div>

      <div className="flex items-center gap-2 mb-4">
        <Button
          dusk="add-exercise-btn"
          size="sm"
          icon={<Plus size={16} />}
          onClick={() => setExercisePickerOpen(true)}
        >
          Add Exercise
        </Button>
        {!selectMode && exerciseBlockCount >= 2 && (
          <Button
            size="sm"
            variant="secondary"
            icon={<Route size={16} />}
            onClick={() => setSelectMode(true)}
          >
            Make a Superset
          </Button>
        )}
      </div>

      {selectMode && (
        <GroupSelectBanner
          selectedCount={selected.length}
          canContinue={selected.length >= 2}
          onContinue={() => setGroupSheetOpen(true)}
          onCancel={() => {
            setSelectMode(false)
            setSelected([])
          }}
        />
      )}

      {blocks.length ? (
        <ReorderList
          items={blocks}
          getKey={b => b.id}
          onReorder={handleReorderBlocks}
          disabled={selectMode}
          className="flex flex-col gap-3"
          itemClassName="rounded-2xl"
          renderItem={(block, { handle, controls }) => {
            if (block.kind === 'group') {
              return (
                <GroupBlockCard
                  block={block}
                  exercises={exercises}
                  allTimeBestMap={allTimeBestMap}
                  handle={handle}
                  controls={controls}
                  onPatchEntry={patchEntry}
                  onUngroup={gid => ungroupMutation.mutate(gid)}
                />
              )
            }

            const ex = exerciseById(exercises, block.exerciseId ?? '')
            if (!ex) return null

            if (selectMode) {
              return (
                <SelectableExerciseCard
                  exercise={ex}
                  equipmentList={equipment}
                  isSelected={selected.includes(block.id)}
                  onToggle={() => toggleSelect(block.id)}
                />
              )
            }

            return (
              <ExerciseBlockCard
                block={block}
                exercise={ex}
                equipmentList={equipment}
                allTimeBestMap={allTimeBestMap}
                handle={handle}
                controls={controls}
                onReorderSets={next => handleReorderSets(block.id, next)}
                onPatchEntry={patchEntry}
                onRemoveSet={handleRemoveSet}
                onAddSet={() => handleAddSet(block)}
                onRemoveExercise={() => setConfirmRemoveExerciseId(ex.id)}
              />
            )
          }}
        />
      ) : (
        <div className="ps-card p-8 text-center text-text-secondary text-sm">
          No exercises yet. Tap{' '}
          <span className="font-semibold text-text-primary">Add Exercise</span> to start logging.
        </div>
      )}

      <div className="ps-card p-4 mt-6">
        <h3 className="font-semibold text-base mb-1">How was the session?</h3>
        <p className="text-[13px] text-text-secondary mb-4">
          Rate after you finish. Helps track recovery.
        </p>
        <div className="flex flex-col gap-4">
          <div>
            <div className="flex items-center gap-1.5 mb-2">
              <Flame size={16} className="text-accent" />
              <span className="label-caps text-text-secondary">Exhaustion</span>
            </div>
            <RatingSlider
              value={workout.exhaustion}
              onChange={v => updateWorkoutMutation.mutate({ exhaustion: v })}
              accent="accent"
            />
          </div>
          <div>
            <div className="flex items-center gap-1.5 mb-2">
              <Target size={16} className="text-secondary" />
              <span className="label-caps text-text-secondary">Soreness</span>
            </div>
            <RatingSlider
              value={workout.soreness}
              onChange={v => updateWorkoutMutation.mutate({ soreness: v })}
              accent="secondary"
            />
          </div>
        </div>
      </div>

      <ExercisePicker
        open={exercisePickerOpen}
        onClose={() => setExercisePickerOpen(false)}
        onSelect={handleAddExercise}
      />
      <GroupConfigSheet
        open={groupSheetOpen}
        onClose={() => setGroupSheetOpen(false)}
        count={selected.length}
        onConfirm={config => {
          createGroupMutation.mutate(config)
          setGroupSheetOpen(false)
        }}
      />
      <ConfirmDialog
        open={confirmDeleteOpen}
        title="Delete workout?"
        message="This session and its logged sets will be removed."
        onCancel={() => setConfirmDeleteOpen(false)}
        onConfirm={() => {
          setConfirmDeleteOpen(false)
          deleteWorkoutMutation.mutate()
        }}
      />
      <ConfirmDialog
        open={confirmRemoveExerciseId !== null}
        title="Remove exercise?"
        message="All logged sets for this exercise in this workout will be removed."
        confirmLabel="Remove"
        onCancel={() => setConfirmRemoveExerciseId(null)}
        onConfirm={() => {
          if (confirmRemoveExerciseId) {
            detachExerciseMutation.mutate(confirmRemoveExerciseId)
          }
          setConfirmRemoveExerciseId(null)
        }}
      />
    </div>
  )
}
