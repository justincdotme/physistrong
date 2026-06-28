import { useState } from 'react'
import { useParams, useNavigate } from 'react-router-dom'
import { Trash2, Plus } from 'lucide-react'
import type { WorkoutTemplate, TemplateExercise, TemplateEntryGroup } from '@/api/types'
import { useTemplate } from '@/hooks/use-templates'
import { useExercises } from '@/hooks/use-exercises'
import { useEquipment } from '@/hooks/use-equipment'
import { useApp } from '@/lib/use-app'
import { exerciseById, equipmentName } from '@/lib/domain'
import { formatDuration } from '@/lib/formatters'
import type { Exercise } from '@/api/types'
import { PageHeader, Button, Card, TypeBadge, InlineEdit, ConfirmDialog } from '@/components/ui'
import { ReorderList } from '@/components/app/reorderable'
import { ExercisePicker, GroupConfigSheet } from '@/components/app/pickers'

interface TemplateBlock {
  kind: 'exercise' | 'group'
  id: string
  te?: TemplateExercise
  group?: TemplateEntryGroup
  order: number
}

function buildBlocks(tpl: WorkoutTemplate): TemplateBlock[] {
  const blocks: TemplateBlock[] = []
  tpl.exercises.forEach(te =>
    blocks.push({
      kind: 'exercise',
      id: `b-${te.id}`,
      te,
      order: te.exerciseOrder,
    })
  )
  tpl.groups.forEach(g => {
    const order = Math.min(...g.exercises.map(e => e.exerciseOrder))
    blocks.push({ kind: 'group', id: `b-${g.id}`, group: g, order })
  })
  return blocks.sort((a, b) => a.order - b.order)
}

function flatten(blocks: TemplateBlock[]): {
  exercises: TemplateExercise[]
  groups: TemplateEntryGroup[]
} {
  let order = 0
  const exercises: TemplateExercise[] = []
  const groups: TemplateEntryGroup[] = []

  blocks.forEach(b => {
    if (b.kind === 'exercise' && b.te) {
      exercises.push({
        ...b.te,
        exerciseOrder: order++,
        groupId: null,
      })
    } else if (b.kind === 'group' && b.group) {
      const ex = b.group.exercises.map(e => ({
        ...e,
        exerciseOrder: order++,
      }))
      groups.push({ ...b.group, exercises: ex })
    }
  })

  return { exercises, groups }
}

export function TemplateEditorPage() {
  const { id: templateId } = useParams<{ id: string }>()
  const navigate = useNavigate()
  const { data: tpl } = useTemplate(templateId ?? '')
  const { data: exercises } = useExercises()
  const { data: equipment } = useEquipment()
  const { updateTemplate, deleteTemplate, uid, toast } = useApp()

  const [exercisePickerOpen, setExercisePickerOpen] = useState(false)
  const [confirmDeleteOpen, setConfirmDeleteOpen] = useState(false)
  const [selectMode, setSelectMode] = useState(false)
  const [selected, setSelected] = useState<string[]>([])
  const [groupSheetOpen, setGroupSheetOpen] = useState(false)

  if (!templateId || !tpl) {
    return (
      <div className="py-16 text-center text-text-muted">
        Template not found.{' '}
        <button className="text-primary font-semibold" onClick={() => navigate('/workouts')}>
          Back
        </button>
      </div>
    )
  }

  const blocks = buildBlocks(tpl)
  const persist = (next: TemplateBlock[]) => {
    updateTemplate(templateId, flatten(next))
  }

  const addExercise = (ex: Exercise) => {
    const te: TemplateExercise = {
      id: uid('te'),
      exerciseId: ex.id,
      exerciseOrder: 0,
      groupId: null,
    }
    persist([{ kind: 'exercise', id: `b-${te.id}`, te, order: 0 }, ...blocks])
    toast('Exercise added.')
  }

  const removeExercise = (blockId: string) => {
    persist(blocks.filter(b => b.id !== blockId))
  }

  const ungroup = (block: TemplateBlock) => {
    if (!block.group) return
    const exBlocks = block.group.exercises.map(e => ({
      kind: 'exercise' as const,
      id: `b-${e.id}`,
      te: { ...e, groupId: null },
      order: e.exerciseOrder,
    }))
    const idx = blocks.findIndex(b => b.id === block.id)
    const next = [...blocks]
    next.splice(idx, 1, ...exBlocks)
    persist(next)
    toast('Group removed.')
  }

  const toggleSelect = (bid: string) =>
    setSelected(s => (s.includes(bid) ? s.filter(x => x !== bid) : [...s, bid]))

  const confirmGroup = (cfg: {
    name: string | null
    plannedRounds: number
    restBetweenExercisesSeconds: number
    restBetweenRoundsSeconds: number
  }) => {
    const gid = uid('tg')
    const chosen = blocks.filter(b => selected.includes(b.id) && b.kind === 'exercise' && b.te)
    const groupExercises: TemplateExercise[] = chosen
      .filter((b): b is TemplateBlock & { te: TemplateExercise } => !!b.te)
      .map(b => ({
        ...b.te,
        groupId: gid,
      }))
    const groupBlock: TemplateBlock = {
      kind: 'group',
      id: `b-${gid}`,
      group: {
        id: gid,
        name: cfg.name,
        plannedRounds: cfg.plannedRounds,
        restBetweenExercisesSeconds: cfg.restBetweenExercisesSeconds,
        restBetweenRoundsSeconds: cfg.restBetweenRoundsSeconds,
        exercises: groupExercises,
      },
      order: chosen[0]?.order || 0,
    }
    const next = [groupBlock, ...blocks.filter(b => !selected.includes(b.id))]
    persist(next)
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
            value={tpl.name}
            onChange={v => updateTemplate(templateId, { name: v })}
            ariaLabel="Template name"
          />
        }
        subtitle="Template"
        actions={
          <button
            onClick={() => setConfirmDeleteOpen(true)}
            aria-label="Delete template"
            className="h-10 w-10 flex items-center justify-center rounded-lg text-text-muted hover:text-destructive hover:bg-surface-muted"
          >
            <Trash2 size={19} />
          </button>
        }
      />

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
          onReorder={persist}
          disabled={selectMode}
          className="flex flex-col gap-3"
          itemClassName="rounded-2xl"
          renderItem={(block, { handle, controls }) => {
            if (block.kind === 'group' && block.group) {
              const g = block.group
              return (
                <div className="pl-3 ml-1" style={{ borderLeft: '2px solid var(--color-primary)' }}>
                  <Card className="p-4">
                    <div className="flex items-center gap-2 mb-3">
                      {handle}
                      <div className="min-w-0 flex-1">
                        <div className="flex items-center gap-2">
                          <span className="font-semibold text-sm truncate">
                            {g.name || 'Superset'}
                          </span>
                          <span
                            className="label-caps px-1.5 py-0.5 rounded-full"
                            style={{
                              color: 'var(--color-primary)',
                              background:
                                'color-mix(in srgb, var(--color-primary) 12%, transparent)',
                            }}
                          >
                            {g.plannedRounds} rounds
                          </span>
                        </div>
                        <div className="text-[12px] text-text-secondary">
                          {formatDuration(g.restBetweenExercisesSeconds)} between ·{' '}
                          {g.restBetweenRoundsSeconds
                            ? `${formatDuration(g.restBetweenRoundsSeconds)} / round`
                            : 'no round rest'}
                        </div>
                      </div>
                      <button
                        onClick={() => ungroup(block)}
                        className="text-[12px] font-semibold text-text-secondary hover:text-destructive px-2 h-8 rounded-lg hover:bg-surface-muted"
                      >
                        Ungroup
                      </button>
                      {controls}
                    </div>
                    <div className="flex flex-col gap-2.5">
                      {g.exercises.map(te => {
                        const ex = exerciseById(exercises, te.exerciseId)
                        if (!ex) return null
                        return (
                          <div key={te.id} className="flex items-center gap-2">
                            <div className="min-w-0 flex-1">
                              <div className="font-semibold text-sm truncate">{ex?.name}</div>
                              <div className="text-[12px] text-text-secondary">
                                {equipmentName(equipment, ex?.equipmentTypeId || null)}
                              </div>
                            </div>
                            <TypeBadge type={ex.type} />
                          </div>
                        )
                      })}
                    </div>
                  </Card>
                </div>
              )
            }

            // Exercise block
            if (!block.te) return null
            const ex = exerciseById(exercises, block.te.exerciseId)
            if (!ex) return null
            const isSel = selected.includes(block.id)

            return (
              <Card
                className="p-4"
                style={
                  selectMode && isSel
                    ? {
                        outline: '2px solid var(--color-primary)',
                        outlineOffset: '-1px',
                      }
                    : undefined
                }
              >
                <div className="flex items-center gap-2">
                  {selectMode ? (
                    <button
                      onClick={() => toggleSelect(block.id)}
                      className="flex items-center gap-2 flex-1 min-w-0 text-left cursor-pointer"
                    >
                      <span
                        className="h-6 w-6 rounded-md border-2 flex items-center justify-center shrink-0"
                        style={
                          isSel
                            ? {
                                background: 'var(--color-primary)',
                                borderColor: 'var(--color-primary)',
                                color: '#fff',
                              }
                            : {
                                borderColor: 'var(--color-border-strong)',
                              }
                        }
                      >
                        {isSel && '✓'}
                      </span>
                      <div className="min-w-0 flex-1">
                        <div className="font-semibold text-sm truncate">{ex.name}</div>
                        <div className="text-[12px] text-text-secondary">
                          {equipmentName(equipment, ex.equipmentTypeId)}
                        </div>
                      </div>
                      <TypeBadge type={ex.type} />
                    </button>
                  ) : (
                    <>
                      {handle}
                      <div className="min-w-0 flex-1">
                        <div className="font-semibold text-sm truncate">{ex.name}</div>
                        <div className="text-[12px] text-text-secondary">
                          {equipmentName(equipment, ex.equipmentTypeId)}
                        </div>
                      </div>
                      <TypeBadge type={ex.type} />
                      <div className="flex items-center gap-1">
                        {controls}
                        <button
                          onClick={() => removeExercise(block.id)}
                          aria-label="Remove from template"
                          title="Remove from template"
                          className="h-10 w-10 flex items-center justify-center rounded-lg text-text-muted hover:text-destructive hover:bg-surface-muted"
                        >
                          <Trash2 size={17} />
                        </button>
                      </div>
                    </>
                  )}
                </div>
              </Card>
            )
          }}
        />
      ) : (
        <div className="ps-card p-8 text-center text-text-secondary text-sm">
          No exercises yet. Tap{' '}
          <span className="font-semibold text-text-primary">Add Exercise</span>.
        </div>
      )}

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
        title="Delete template?"
        message={`"${tpl.name}" will be removed.`}
        onCancel={() => setConfirmDeleteOpen(false)}
        onConfirm={() => {
          deleteTemplate(templateId)
          toast('Template deleted.')
          navigate('/workouts')
        }}
      />
    </>
  )
}
