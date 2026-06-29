import { useState } from 'react'
import { useParams, useNavigate } from 'react-router-dom'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { Trash2, Plus } from 'lucide-react'
import type { WorkoutTemplate, TemplateExercise, TemplateEntryGroup } from '@/api/types'
import {
  getTemplate,
  updateTemplate as updateTemplateApi,
  deleteTemplate as deleteTemplateApi,
  attachExercise as attachExerciseApi,
  detachExercise as detachExerciseApi,
  reorderExercises as reorderExercisesApi,
  createTemplateGroup,
  deleteTemplateGroup,
  assignExercisesToGroup,
} from '@/api/templates'
import { listEquipment } from '@/api/equipment'
import { useApp } from '@/lib/use-app'
import { equipmentName } from '@/lib/domain'
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

export function TemplateEditorPage() {
  const { id: templateId } = useParams<{ id: string }>()
  const navigate = useNavigate()
  const queryClient = useQueryClient()
  const { toast } = useApp()

  const [exercisePickerOpen, setExercisePickerOpen] = useState(false)
  const [confirmDeleteOpen, setConfirmDeleteOpen] = useState(false)
  const [selectMode, setSelectMode] = useState(false)
  const [selected, setSelected] = useState<string[]>([])
  const [groupSheetOpen, setGroupSheetOpen] = useState(false)

  const { data: tpl, isLoading } = useQuery({
    queryKey: ['templates', templateId],
    queryFn: () => getTemplate(templateId ?? ''),
    enabled: !!templateId,
  })

  const { data: equipment = [] } = useQuery({
    queryKey: ['equipment'],
    queryFn: listEquipment,
  })

  const updateNameMutation = useMutation({
    mutationFn: ({ name }: { name: string }) => updateTemplateApi(templateId ?? '', { name }),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['templates', templateId] })
      queryClient.invalidateQueries({ queryKey: ['templates'] })
    },
  })

  const deleteMutation = useMutation({
    mutationFn: () => deleteTemplateApi(templateId ?? ''),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['templates'] })
      toast('Template deleted.')
      navigate('/workouts')
    },
  })

  const attachMutation = useMutation({
    mutationFn: ({ exerciseId }: { exerciseId: string }) =>
      attachExerciseApi(templateId ?? '', exerciseId),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['templates', templateId] })
      toast('Exercise added.')
    },
  })

  const detachMutation = useMutation({
    mutationFn: ({ exerciseId }: { exerciseId: string }) =>
      detachExerciseApi(templateId ?? '', exerciseId),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['templates', templateId] })
    },
  })

  const reorderMutation = useMutation({
    mutationFn: ({ ids }: { ids: string[] }) => reorderExercisesApi(templateId ?? '', ids),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['templates', templateId] })
    },
  })

  const createGroupMutation = useMutation({
    mutationFn: async (config: {
      name: string | null
      plannedRounds: number
      restBetweenExercisesSeconds: number
      restBetweenRoundsSeconds: number
    }) => {
      if (!tpl) throw new Error('Template not loaded')
      const afterCreate = await createTemplateGroup(templateId ?? '', {
        name: config.name,
        planned_rounds: config.plannedRounds,
        rest_between_exercises_seconds: config.restBetweenExercisesSeconds,
        rest_between_rounds_seconds: config.restBetweenRoundsSeconds,
      })

      const existingIds = new Set(tpl.groups.map(g => g.id))
      const newGroup = afterCreate.groups.find(g => !existingIds.has(g.id))
      if (!newGroup) throw new Error('Group not created')

      const currentBlocks = buildBlocks(tpl)
      const exerciseIds = currentBlocks
        .filter(
          (b): b is TemplateBlock & { te: TemplateExercise } =>
            selected.includes(b.id) && b.kind === 'exercise' && !!b.te
        )
        .map(b => b.te.exerciseId)

      return assignExercisesToGroup(templateId ?? '', newGroup.id, exerciseIds)
    },
    onSuccess: data => {
      queryClient.setQueryData(['templates', templateId], data)
      setSelectMode(false)
      setSelected([])
      toast('Group created.')
    },
    onError: () => toast('Could not create group. Try again.'),
  })

  const ungroupMutation = useMutation({
    mutationFn: (groupId: string) => deleteTemplateGroup(templateId ?? '', groupId),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['templates', templateId] })
      toast('Group removed.')
    },
    onError: () => toast('Could not ungroup. Try again.'),
  })

  if (!templateId) {
    return (
      <div className="py-16 text-center text-text-muted">
        Template not found.{' '}
        <button className="text-primary font-semibold" onClick={() => navigate('/workouts')}>
          Back
        </button>
      </div>
    )
  }

  if (isLoading) {
    return <div className="py-16 text-center text-text-muted">Loading template...</div>
  }

  if (!tpl) {
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

  const addExercise = (ex: Exercise) => {
    attachMutation.mutate({ exerciseId: ex.id })
    setExercisePickerOpen(false)
  }

  const removeExercise = (blockId: string) => {
    const block = blocks.find(b => b.id === blockId)
    if (block?.kind === 'exercise' && block.te) {
      detachMutation.mutate({ exerciseId: block.te.exerciseId })
    }
  }

  const handleReorder = (newBlocks: TemplateBlock[]) => {
    const ids: string[] = []
    newBlocks.forEach(b => {
      if (b.kind === 'exercise' && b.te) {
        ids.push(b.te.exerciseId)
      } else if (b.kind === 'group' && b.group) {
        b.group.exercises.forEach(e => ids.push(e.exerciseId))
      }
    })
    reorderMutation.mutate({ ids })
  }

  const ungroup = (block: TemplateBlock) => {
    if (!block.group) return
    ungroupMutation.mutate(block.group.id)
  }

  const exerciseBlockCount = blocks.filter(b => b.kind === 'exercise').length

  return (
    <>
      <PageHeader
        back
        onBack={() => navigate('/workouts')}
        title={
          <InlineEdit
            value={tpl.name}
            onChange={v => updateNameMutation.mutate({ name: v })}
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
                Tap 2 or more exercises to group them.
              </div>
            </div>
          </div>
          <div className="flex items-center gap-2 mt-3">
            <Button
              size="sm"
              disabled={selected.length < 2}
              onClick={() => setGroupSheetOpen(true)}
            >
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
          onReorder={handleReorder}
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
                      {g.exercises.map(te => (
                        <div key={te.id} className="flex items-center gap-2">
                          <div className="min-w-0 flex-1">
                            <div className="font-semibold text-sm truncate">{te.name}</div>
                            <div className="text-[12px] text-text-secondary">
                              {equipmentName(equipment, te.equipmentTypeId || null)}
                            </div>
                          </div>
                          <TypeBadge type={te.type} />
                        </div>
                      ))}
                    </div>
                  </Card>
                </div>
              )
            }

            // Exercise block
            if (!block.te) return null
            const te = block.te

            if (selectMode) {
              const isSelected = selected.includes(block.id)
              return (
                <Card
                  className="p-4"
                  style={
                    isSelected
                      ? { outline: '2px solid var(--color-primary)', outlineOffset: '-1px' }
                      : undefined
                  }
                >
                  <button
                    onClick={() =>
                      setSelected(s =>
                        s.includes(block.id) ? s.filter(x => x !== block.id) : [...s, block.id]
                      )
                    }
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
                      <div className="font-semibold text-sm truncate">{te.name}</div>
                      <div className="text-[12px] text-text-secondary">
                        {equipmentName(equipment, te.equipmentTypeId || null)}
                      </div>
                    </div>
                    <TypeBadge type={te.type} />
                  </button>
                </Card>
              )
            }

            return (
              <Card className="p-4">
                <div className="flex items-center gap-2">
                  {handle}
                  <div className="min-w-0 flex-1">
                    <div className="font-semibold text-sm truncate">{te.name}</div>
                    <div className="text-[12px] text-text-secondary">
                      {equipmentName(equipment, te.equipmentTypeId || null)}
                    </div>
                  </div>
                  <TypeBadge type={te.type} />
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
      <ConfirmDialog
        open={confirmDeleteOpen}
        title="Delete template?"
        message={`"${tpl.name}" will be removed.`}
        onCancel={() => setConfirmDeleteOpen(false)}
        onConfirm={() => deleteMutation.mutate()}
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
    </>
  )
}
