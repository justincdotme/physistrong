import { useState, useMemo } from 'react'
import { useParams, useNavigate } from 'react-router-dom'
import { Trash2, Flame, Target, Plus } from 'lucide-react'
import type { WorkoutEntry, EntryGroup, Exercise } from '@/api/types'
import { useWorkout } from '@/hooks/use-workouts'
import { useExercises } from '@/hooks/use-exercises'
import { useEquipment } from '@/hooks/use-equipment'
import { useApp } from '@/lib/use-app'
import { formatDuration } from '@/lib/formatters'
import {
  workoutCompletion,
  exerciseById,
  equipmentName,
  entryHasActual,
  bestWeight,
} from '@/lib/domain'
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

function flatten(blocks: Block[]): WorkoutEntry[] {
  const out: WorkoutEntry[] = []
  blocks.forEach(b => b.entries.forEach(e => out.push(e)))
  return out.map((e, idx) => ({ ...e, setOrder: idx }))
}

function freshEntry(
  ex: Exercise,
  workoutId: string,
  uid: (p: string) => string,
  distanceUnit: string
): WorkoutEntry {
  const base = {
    id: uid('we'),
    workoutId,
    exerciseId: ex.id,
    setOrder: 0,
    entryGroupId: null,
    groupRound: null,
    notes: null,
  } as const

  if (ex.type === 'resistance') {
    return {
      ...base,
      loadMetric: {
        targetWeight: null,
        actualWeight: null,
        bodyweightOnly: !!ex.bodyweightBase,
      },
      repMetric: {
        targetReps: null,
        actualReps: null,
        toFailure: false,
        failureRep: null,
      },
    } as WorkoutEntry
  }

  if (ex.type === 'timed_hold') {
    return {
      ...base,
      durationMetric: {
        targetDurationSeconds: null,
        actualDurationSeconds: null,
      },
    } as WorkoutEntry
  }

  if (ex.type === 'distance') {
    return {
      ...base,
      distanceMetric: {
        targetDistance: null,
        actualDistance: null,
        distanceUnit,
        lapCount: null,
        strokeCount: null,
      },
      durationMetric: {
        targetDurationSeconds: null,
        actualDurationSeconds: null,
      },
    } as WorkoutEntry
  }

  // interval
  const n = ex.defaultRounds || 8
  const rounds = []
  for (let k = 1; k <= n; k++) {
    rounds.push({
      roundNumber: k,
      actualWorkSeconds: null,
      actualRestSeconds: null,
      heartRateAvg: null,
      heartRatePeak: null,
    })
  }
  return {
    ...base,
    intervalHeader: {
      programmedRounds: n,
      completedRounds: 0,
      targetWorkSeconds: ex.defaultWorkSeconds || 60,
      targetRestSeconds: ex.defaultRestSeconds || 60,
      rounds,
    },
  } as WorkoutEntry
}

interface ExerciseHeaderProps {
  exercise: Exercise
  handle: React.ReactNode
  controls: React.ReactNode
  equipment: ReturnType<typeof useEquipment>['data']
}

function ExerciseHeader({ exercise, handle, controls, equipment }: ExerciseHeaderProps) {
  return (
    <div className="flex items-center gap-2 mb-3">
      {handle}
      <div className="min-w-0 flex-1">
        <div className="font-semibold text-base truncate">{exercise.name}</div>
        <div className="text-[12px] text-text-secondary">
          {equipmentName(equipment, exercise.equipmentTypeId)}
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
  allTimeBest: number | null
  onPatch: (patch: Partial<WorkoutEntry>) => void
  onRemove: () => void
}

function SetRow({
  entry,
  exercise,
  index,
  handle,
  controls,
  allTimeBest,
  onPatch,
  onRemove,
}: SetRowProps) {
  return (
    <div className="flex items-start gap-2">
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

export function WorkoutDetailPage() {
  const { id: workoutId } = useParams<{ id: string }>()
  const navigate = useNavigate()
  const { data: workout } = useWorkout(workoutId ?? '')
  const { data: exercises } = useExercises()
  const { data: equipment } = useEquipment()
  const { updateWorkout, deleteWorkout, uid, toast, workouts, user } = useApp()

  const [exercisePickerOpen, setExercisePickerOpen] = useState(false)
  const [confirmDeleteOpen, setConfirmDeleteOpen] = useState(false)
  const [selectMode, setSelectMode] = useState(false)
  const [selected, setSelected] = useState<string[]>([])
  const [groupSheetOpen, setGroupSheetOpen] = useState(false)

  const bestWeights = useMemo(() => {
    const m: Record<string, number | null> = {}
    exercises.forEach(ex => {
      if (ex.type === 'resistance') {
        m[ex.id] = bestWeight(workouts, ex.id)
      }
    })
    return m
  }, [workouts, exercises])

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

  const persist = (newBlocks: Block[]) => {
    updateWorkout(workoutId, { entries: flatten(newBlocks) })
  }

  const patchEntry = (entryId: string, patch: Partial<WorkoutEntry>) => {
    updateWorkout(workoutId, {
      entries: workout.entries.map(e => (e.id === entryId ? { ...e, ...patch } : e)),
    })
  }

  const reorderBlocks = (next: Block[]) => persist(next)

  const reorderSets = (blockId: string, nextEntries: WorkoutEntry[]) => {
    persist(blocks.map(b => (b.id === blockId ? { ...b, entries: nextEntries } : b)))
  }

  const addSet = (block: Block) => {
    const ex = exerciseById(exercises, block.exerciseId ?? '')
    if (!ex) return
    const ref = block.entries[0]
    if (!ref) return
    const distanceUnit = user.measurementSystem === 'imperial' ? 'miles' : 'kilometers'
    const ne: WorkoutEntry = {
      ...freshEntry(ex, workoutId, uid, distanceUnit),
      loadMetric: ref.loadMetric ? { ...ref.loadMetric, actualWeight: null } : undefined,
      repMetric: ref.repMetric
        ? { ...ref.repMetric, actualReps: null, toFailure: false, failureRep: null }
        : undefined,
    }
    reorderSets(block.id, [ne, ...block.entries])
  }

  const removeSet = (block: Block, entryId: string) => {
    const next = block.entries.filter(e => e.id !== entryId)
    if (!next.length) {
      persist(blocks.filter(b => b.id !== block.id))
    } else {
      reorderSets(block.id, next)
    }
  }

  const addExercise = (ex: Exercise) => {
    const distanceUnit = user.measurementSystem === 'imperial' ? 'miles' : 'kilometers'
    const ne = freshEntry(ex, workoutId, uid, distanceUnit)
    persist([
      { kind: 'exercise', id: `b-new-${ne.id}`, exerciseId: ex.id, entries: [ne] },
      ...blocks,
    ])
    toast('Exercise added.')
  }

  const ungroup = (block: Block) => {
    const cleared = block.entries.map(e => ({
      ...e,
      entryGroupId: null,
      groupRound: null,
    }))
    const newBlocks = blocks.map(b =>
      b.id === block.id ? { ...b, kind: 'exercise', entries: cleared } : b
    )
    updateWorkout(workoutId, {
      entries: flatten(newBlocks as Block[]),
      entryGroups: workout.entryGroups.filter(g => g.id !== block.gid),
    })
    toast('Group removed.')
  }

  const toggleSelect = (blockId: string) =>
    setSelected(s => (s.includes(blockId) ? s.filter(x => x !== blockId) : [...s, blockId]))

  const confirmGroup = (cfg: {
    name: string | null
    plannedRounds: number
    restBetweenExercisesSeconds: number
    restBetweenRoundsSeconds: number
  }) => {
    const gid = uid('grp')
    const chosen = blocks.filter(b => selected.includes(b.id) && b.kind === 'exercise')
    const groupEntries: WorkoutEntry[] = []

    const distanceUnit = user.measurementSystem === 'imperial' ? 'miles' : 'kilometers'
    for (let r = 1; r <= cfg.plannedRounds; r++) {
      chosen.forEach(b => {
        const ex = exerciseById(exercises, b.exerciseId ?? '')
        if (ex) {
          const e = freshEntry(ex, workoutId, uid, distanceUnit)
          groupEntries.push({
            ...e,
            entryGroupId: gid,
            groupRound: r,
          })
        }
      })
    }

    const groupBlock: Block = {
      kind: 'group',
      id: `b-${gid}`,
      gid,
      group: { id: gid, workoutId, ...cfg },
      entries: groupEntries,
    }

    const newBlocks = [groupBlock, ...blocks.filter(b => !selected.includes(b.id))]
    updateWorkout(workoutId, {
      entries: flatten(newBlocks),
      entryGroups: [...workout.entryGroups, { id: gid, workoutId, ...cfg }],
    })
    setSelected([])
    setSelectMode(false)
    toast('Group created.')
  }

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
            onChange={v => updateWorkout(workoutId, { name: v })}
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
              onChange={e => updateWorkout(workoutId, { date: e.target.value })}
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
          onReorder={reorderBlocks}
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
                      <button
                        onClick={() => ungroup(block)}
                        title="Ungroup exercises"
                        className="text-[12px] font-semibold text-text-secondary hover:text-destructive px-2 h-9 rounded-lg hover:bg-surface-muted"
                      >
                        Ungroup
                      </button>
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
                                      allTimeBest={bestWeights[ex.id] || null}
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

            // Exercise block
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
                      equipment={equipment}
                    />
                    <ReorderList
                      items={block.entries}
                      getKey={e => e.id}
                      onReorder={next => reorderSets(block.id, next)}
                      className="flex flex-col gap-3"
                      itemClassName="rounded-lg"
                      renderItem={(entry, { index, handle: h, controls: c }) => (
                        <SetRow
                          entry={entry}
                          exercise={ex}
                          index={index}
                          handle={h}
                          controls={c}
                          allTimeBest={bestWeights[ex.id] || null}
                          onPatch={p => patchEntry(entry.id, p)}
                          onRemove={() => removeSet(block, entry.id)}
                        />
                      )}
                    />
                    <Button
                      variant="secondary"
                      size="sm"
                      icon={<Plus size={16} />}
                      full
                      className="mt-3"
                      onClick={() => addSet(block)}
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
              onChange={v => updateWorkout(workoutId, { exhaustion: v })}
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
              onChange={v => updateWorkout(workoutId, { soreness: v })}
              accent="secondary"
            />
          </div>
        </div>
      </div>

      <ExercisePicker
        open={exercisePickerOpen}
        onClose={() => setExercisePickerOpen(false)}
        onSelect={addExercise}
      />
      <GroupConfigSheet
        open={groupSheetOpen}
        onClose={() => setGroupSheetOpen(false)}
        count={selected.length}
        onConfirm={confirmGroup}
      />
      <ConfirmDialog
        open={confirmDeleteOpen}
        title="Delete workout?"
        message="This session and its logged sets will be removed."
        onCancel={() => setConfirmDeleteOpen(false)}
        onConfirm={() => {
          deleteWorkout(workoutId)
          toast('Workout deleted.')
          navigate('/workouts')
        }}
      />
    </>
  )
}
