import { useState, useRef, useCallback, useEffect } from 'react'
import { useParams, useNavigate } from 'react-router-dom'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { Trash2, Flame, Target, Plus } from 'lucide-react'
import type { WorkoutEntry, EntryGroup, Exercise, Workout } from '@/api/types'
import { listExercises } from '@/api/exercises'
import { listEquipment } from '@/api/equipment'
import {
  getWorkout,
  updateWorkout as updateWorkoutApi,
  deleteWorkout as deleteWorkoutApi,
  attachExercise,
  reorderExercises,
  createEntry,
  updateEntry,
  deleteEntry,
  reorderEntries,
} from '@/api/workouts'
import { toMetricsPayload } from '@/api/transformers'
import type { CreateEntryPayload } from '@/api/workouts'
import { useApp } from '@/lib/use-app'
import { formatDuration } from '@/lib/formatters'
import { workoutCompletion, exerciseById, equipmentName, entryHasActual } from '@/lib/domain'
import {
  PageHeader,
  Button,
  Card,
  TypeBadge,
  CompletionBar,
  InlineEdit,
  ConfirmDialog,
  RatingSlider,
} from '@/components/ui'
import { ReorderList } from '@/components/app/reorderable'
import { EntryMetrics } from '@/components/app/metric-inputs'
import { ExercisePicker, GroupConfigSheet } from '@/components/app/pickers'

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

function defaultEntryPayload(
  ex: Exercise,
  setOrder: number,
  distanceUnit: string
): CreateEntryPayload {
  const base: CreateEntryPayload = {
    exercise_id: Number(ex.id),
    set_order: setOrder,
    metrics: {},
  }

  if (ex.type === 'resistance') {
    base.metrics = {
      load: {
        target_weight: null,
        actual_weight: null,
        bodyweight_only: !!ex.bodyweightBase,
      },
      reps: {
        target_reps: null,
        actual_reps: null,
        to_failure: false,
        failure_rep: null,
      },
    }
  } else if (ex.type === 'timed_hold') {
    base.metrics = {
      duration: {
        target_duration_seconds: null,
        actual_duration_seconds: null,
      },
    }
  } else if (ex.type === 'distance') {
    base.metrics = {
      distance: {
        target_distance: null,
        actual_distance: null,
        distance_unit: distanceUnit,
        lap_count: null,
        stroke_count: null,
      },
      duration: {
        target_duration_seconds: null,
        actual_duration_seconds: null,
      },
    }
  } else if (ex.type === 'interval') {
    const n = ex.defaultRounds || 8
    const rounds = Array.from({ length: n }, (_, k) => ({
      round_number: k + 1,
      actual_work_seconds: null,
      actual_rest_seconds: null,
      heart_rate_avg: null,
      heart_rate_peak: null,
    }))
    base.metrics = {
      interval_header: {
        programmed_rounds: n,
        completed_rounds: 0,
        target_work_seconds: ex.defaultWorkSeconds || 60,
        target_rest_seconds: ex.defaultRestSeconds || 60,
        rounds,
      },
    }
  }

  return base
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
  equipmentList: Array<{ id: string; name: string; isSystem: boolean }>
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
}

function SetRow({ entry, exercise, index, handle, controls, onPatch, onRemove }: SetRowProps) {
  return (
    <div className="flex items-start gap-2">
      <div className="flex flex-col items-center shrink-0">
        <span className="label-caps text-text-muted mb-0.5">{index + 1}</span>
        {handle}
        {controls}
      </div>
      <div className="flex-1 min-w-0 pt-1">
        <EntryMetrics entry={entry} exercise={exercise} allTimeBest={null} onChange={onPatch} />
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

export function WorkoutDetailPage() {
  const { id: workoutId = '' } = useParams<{ id: string }>()
  const navigate = useNavigate()
  const queryClient = useQueryClient()
  const { toast, user } = useApp()

  const { data: workout, isLoading } = useQuery({
    queryKey: ['workouts', workoutId],
    queryFn: () => getWorkout(workoutId),
    enabled: !!workoutId,
  })

  const { data: exercises = [] } = useQuery({
    queryKey: ['exercises'],
    queryFn: listExercises,
  })

  const { data: equipment = [] } = useQuery({
    queryKey: ['equipment'],
    queryFn: listEquipment,
  })

  const [exercisePickerOpen, setExercisePickerOpen] = useState(false)
  const [confirmDeleteOpen, setConfirmDeleteOpen] = useState(false)
  const [selectMode, setSelectMode] = useState(false)
  const [selected, setSelected] = useState<string[]>([])
  const [groupSheetOpen, setGroupSheetOpen] = useState(false)

  const pendingUpdates = useRef(new Map<string, ReturnType<typeof setTimeout>>())

  useEffect(() => {
    const timers = pendingUpdates.current
    return () => {
      timers.forEach(t => clearTimeout(t))
    }
  }, [])

  const invalidateWorkout = useCallback(() => {
    queryClient.invalidateQueries({ queryKey: ['workouts', workoutId] })
  }, [queryClient, workoutId])

  const updateWorkoutMutation = useMutation({
    mutationFn: (payload: Parameters<typeof updateWorkoutApi>[1]) =>
      updateWorkoutApi(workoutId, payload),
    onSuccess: data => {
      queryClient.setQueryData(['workouts', workoutId], data)
    },
    onError: () => toast('Could not save. Try again.'),
  })

  const deleteWorkoutMutation = useMutation({
    mutationFn: () => deleteWorkoutApi(workoutId),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['workouts'] })
      toast('Workout deleted.')
      navigate('/workouts')
    },
    onError: () => toast('Could not delete. Try again.'),
  })

  const attachExerciseMutation = useMutation({
    mutationFn: async (ex: Exercise) => {
      await attachExercise(workoutId, ex.id)
      const du = user.measurementSystem === 'imperial' ? 'miles' : 'kilometers'
      const nextOrder = workout ? workout.entries.length : 0
      await createEntry(workoutId, defaultEntryPayload(ex, nextOrder, du))
    },
    onSuccess: () => {
      invalidateWorkout()
      toast('Exercise added.')
    },
    onError: () => toast('Could not add exercise. Try again.'),
  })

  const reorderExercisesMutation = useMutation({
    mutationFn: (ids: string[]) => reorderExercises(workoutId, ids),
    onSuccess: data => {
      queryClient.setQueryData(['workouts', workoutId], data)
    },
  })

  const addSetMutation = useMutation({
    mutationFn: (payload: CreateEntryPayload) => createEntry(workoutId, payload),
    onSuccess: invalidateWorkout,
    onError: () => toast('Could not add set. Try again.'),
  })

  const deleteEntryMutation = useMutation({
    mutationFn: (entryId: string) => deleteEntry(workoutId, entryId),
    onSuccess: (_data, entryId) => {
      queryClient.setQueryData(['workouts', workoutId], (old: Workout | undefined) => {
        if (!old) return old
        return { ...old, entries: old.entries.filter(e => e.id !== entryId) }
      })
    },
    onError: () => toast('Could not remove set. Try again.'),
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
      queryClient.setQueryData(['workouts', workoutId], (old: Workout | undefined) => {
        if (!old) return old
        return {
          ...old,
          entries: old.entries.map(e => (e.id === updatedEntry.id ? updatedEntry : e)),
        }
      })
    },
  })

  const reorderEntriesMutation = useMutation({
    mutationFn: (ids: string[]) => reorderEntries(workoutId, ids),
    onSuccess: invalidateWorkout,
  })

  const debouncedEntryUpdate = useCallback(
    (entryId: string, entry: WorkoutEntry) => {
      const pending = pendingUpdates.current
      const existing = pending.get(entryId)
      if (existing) clearTimeout(existing)

      const timer = setTimeout(() => {
        pending.delete(entryId)
        updateEntryMutation.mutate({
          entryId,
          payload: { metrics: toMetricsPayload(entry) },
        })
      }, 800)
      pending.set(entryId, timer)
    },
    [updateEntryMutation]
  )

  const patchEntry = useCallback(
    (entryId: string, patch: Partial<WorkoutEntry>) => {
      const oldWorkout = queryClient.getQueryData<Workout>(['workouts', workoutId])
      if (!oldWorkout) return

      const updatedEntries = oldWorkout.entries.map(e =>
        e.id === entryId ? { ...e, ...patch } : e
      )
      queryClient.setQueryData(['workouts', workoutId], {
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

  if (isLoading) {
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
  const distanceUnit = user.measurementSystem === 'imperial' ? 'miles' : 'kilometers'

  const handleReorderBlocks = (next: Block[]) => {
    const allEntries = next.flatMap(b => b.entries)
    const ids = allEntries.map(e => e.id)

    queryClient.setQueryData(['workouts', workoutId], (old: Workout | undefined) => {
      if (!old) return old
      return {
        ...old,
        entries: allEntries.map((e, idx) => ({ ...e, setOrder: idx })),
      }
    })

    const exerciseIds = next
      .filter((b): b is Block & { exerciseId: string } => b.kind === 'exercise' && !!b.exerciseId)
      .map(b => b.exerciseId)
    const uniqueExerciseIds = [...new Set(exerciseIds)]
    if (uniqueExerciseIds.length > 1) {
      reorderExercisesMutation.mutate(uniqueExerciseIds)
    }
    reorderEntriesMutation.mutate(ids)
  }

  const handleReorderSets = (blockId: string, nextEntries: WorkoutEntry[]) => {
    const newBlocks = blocks.map(b => (b.id === blockId ? { ...b, entries: nextEntries } : b))
    const allEntries = newBlocks.flatMap(b => b.entries)
    const ids = allEntries.map(e => e.id)

    queryClient.setQueryData(['workouts', workoutId], (old: Workout | undefined) => {
      if (!old) return old
      return {
        ...old,
        entries: allEntries.map((e, idx) => ({ ...e, setOrder: idx })),
      }
    })

    reorderEntriesMutation.mutate(ids)
  }

  const handleAddSet = (block: Block) => {
    const ex = exerciseById(exercises, block.exerciseId ?? '')
    if (!ex) return
    const nextOrder = workout.entries.length
    addSetMutation.mutate(defaultEntryPayload(ex, nextOrder, distanceUnit))
  }

  const handleRemoveSet = (entryId: string) => {
    deleteEntryMutation.mutate(entryId)
  }

  const handleAddExercise = (ex: Exercise) => {
    attachExerciseMutation.mutate(ex)
  }

  const toggleSelect = (blockId: string) =>
    setSelected(s => (s.includes(blockId) ? s.filter(x => x !== blockId) : [...s, blockId]))

  const canGroup = selectMode && selected.length >= 2
  const exerciseBlockCount = blocks.filter(b => b.kind === 'exercise').length

  return (
    <>
      <PageHeader
        back
        onBack={() => navigate('/workouts')}
        title={
          <InlineEdit
            value={workout.name}
            onChange={v => updateWorkoutMutation.mutate({ name: v })}
            ariaLabel="Workout name"
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
            <span>📅</span>
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
        <Button size="sm" icon={<Plus size={16} />} onClick={() => setExercisePickerOpen(true)}>
          Add Exercise
        </Button>
        {!selectMode && exerciseBlockCount >= 2 && (
          <Button
            size="sm"
            variant="secondary"
            icon={<span>⊕</span>}
            onClick={() => setSelectMode(true)}
          >
            Make a Superset
          </Button>
        )}
      </div>

      {selectMode && (
        <div
          className="ps-card p-4 mb-4"
          style={{
            border: '1px solid var(--color-primary)',
            background: 'color-mix(in srgb, var(--color-primary) 6%, var(--color-surface-card))',
          }}
        >
          <div className="flex items-start gap-3">
            <span
              className="h-9 w-9 rounded-lg flex items-center justify-center shrink-0"
              style={{
                background: 'color-mix(in srgb, var(--color-primary) 14%, transparent)',
                color: 'var(--color-primary)',
              }}
            >
              ✓
            </span>
            <div className="flex-1 min-w-0">
              <div className="font-semibold text-sm">Build a superset or circuit</div>
              <div className="text-[13px] text-text-secondary mt-0.5">
                Tap 2 or more exercises below to combine them. You will set rounds and rest in the
                next step.
              </div>
            </div>
          </div>
          <div className="flex items-center gap-2 mt-3">
            <Button size="sm" disabled={!canGroup} onClick={() => setGroupSheetOpen(true)}>
              Continue · {selected.length} selected
            </Button>
            <Button
              size="sm"
              variant="ghost"
              onClick={() => {
                setSelectMode(false)
                setSelected([])
              }}
            >
              Cancel
            </Button>
          </div>
        </div>
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
              const g = block.group
              const rounds =
                g?.plannedRounds || Math.max(...block.entries.map(e => e.groupRound || 1))
              const roundComplete = (r: number) =>
                block.entries.filter(e => e.groupRound === r).every(e => entryHasActual(e))
              const completed: number[] = []
              for (let r = 1; r <= rounds; r++) {
                if (roundComplete(r)) completed.push(r)
              }

              return (
                <div className="pl-3 ml-1" style={{ borderLeft: '2px solid var(--color-primary)' }}>
                  <Card className="p-4">
                    <div className="flex items-center gap-2 mb-3">
                      {handle}
                      <div className="min-w-0 flex-1">
                        <div className="flex items-center gap-2">
                          <span className="font-semibold text-base truncate">
                            {g?.name || 'Superset'}
                          </span>
                          <span
                            className="label-caps px-1.5 py-0.5 rounded-full"
                            style={{
                              color: 'var(--color-primary)',
                              background:
                                'color-mix(in srgb, var(--color-primary) 12%, transparent)',
                            }}
                          >
                            Round{' '}
                            {Math.min(
                              completed.length + (completed.length < rounds ? 1 : 0),
                              rounds
                            )}{' '}
                            of {rounds}
                          </span>
                        </div>
                        <div className="text-[12px] text-text-secondary">
                          {formatDuration(g?.restBetweenExercisesSeconds || 0)} between ·{' '}
                          {g?.restBetweenRoundsSeconds
                            ? `${formatDuration(g.restBetweenRoundsSeconds)} / round`
                            : 'no round rest'}
                        </div>
                      </div>
                      {controls}
                    </div>
                    <div className="flex flex-col gap-4">
                      {Array.from({ length: rounds }, (_, ri) => ri + 1).map(r => (
                        <div key={r}>
                          <div className="flex items-center gap-2 mb-2">
                            <span className="label-caps text-text-muted">Round {r}</span>
                            <span
                              className="h-px flex-1"
                              style={{ background: 'var(--color-border)' }}
                            />
                            {completed.includes(r) && (
                              <span style={{ color: 'var(--color-success)' }}>✓</span>
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
                                    <div className="text-[13px] font-semibold mb-1.5">
                                      {ex.name}
                                    </div>
                                    <EntryMetrics
                                      entry={entry}
                                      exercise={ex}
                                      allTimeBest={null}
                                      onChange={p => patchEntry(entry.id, p)}
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

            const ex = exerciseById(exercises, block.exerciseId ?? '')
            if (!ex) return null
            const isSelected = selected.includes(block.id)

            return (
              <Card
                className="p-4"
                style={
                  selectMode && isSelected
                    ? {
                        outline: '2px solid var(--color-primary)',
                        outlineOffset: '-1px',
                      }
                    : undefined
                }
              >
                {selectMode ? (
                  <button
                    onClick={() => toggleSelect(block.id)}
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
                      {isSelected && '✓'}
                    </span>
                    <div className="min-w-0 flex-1">
                      <div className="font-semibold text-base truncate">{ex.name}</div>
                      <div className="text-[12px] text-text-secondary">
                        {equipmentName(equipment, ex.equipmentTypeId)}
                      </div>
                    </div>
                    <TypeBadge type={ex.type} />
                  </button>
                ) : (
                  <>
                    <ExerciseHeader
                      exercise={ex}
                      handle={handle}
                      controls={controls}
                      equipmentList={equipment}
                    />
                    <ReorderList
                      items={block.entries}
                      getKey={e => e.id}
                      onReorder={next => handleReorderSets(block.id, next)}
                      className="flex flex-col gap-3"
                      itemClassName="rounded-lg"
                      renderItem={(entry, { index, handle: h, controls: c }) => (
                        <SetRow
                          entry={entry}
                          exercise={ex}
                          index={index}
                          handle={h}
                          controls={c}
                          onPatch={p => patchEntry(entry.id, p)}
                          onRemove={() => handleRemoveSet(entry.id)}
                        />
                      )}
                    />
                    <Button
                      variant="secondary"
                      size="sm"
                      icon={<Plus size={16} />}
                      full
                      className="mt-3"
                      onClick={() => handleAddSet(block)}
                    >
                      Add Set
                    </Button>
                  </>
                )}
              </Card>
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
          Rate after you finish — helps track recovery.
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
        onConfirm={() => {
          toast('Supersets require the grouping API (Phase 8a).')
          setGroupSheetOpen(false)
          setSelectMode(false)
          setSelected([])
        }}
      />
      <ConfirmDialog
        open={confirmDeleteOpen}
        title="Delete workout?"
        message="This session and its logged sets will be removed."
        onCancel={() => setConfirmDeleteOpen(false)}
        onConfirm={() => deleteWorkoutMutation.mutate()}
      />
    </>
  )
}
