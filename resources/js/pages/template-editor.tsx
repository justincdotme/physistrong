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
} from '@/api/templates'
import { listEquipment } from '@/api/equipment'
import { useApp } from '@/lib/use-app'
import { equipmentName } from '@/lib/domain'
import { formatDuration } from '@/lib/formatters'
import type { Exercise } from '@/api/types'
import { PageHeader, Button, Card, TypeBadge, InlineEdit, ConfirmDialog } from '@/components/ui'
import { ReorderList } from '@/components/app/reorderable'
import { ExercisePicker } from '@/components/app/pickers'

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
    toast('Coming in a future update')
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
        {exerciseBlockCount >= 2 && (
          <Button
            size="sm"
            variant="secondary"
            icon={<span>⊕</span>}
            onClick={() => toast('Coming in a future update')}
          >
            Make a Superset
          </Button>
        )}
      </div>

      {blocks.length ? (
        <ReorderList
          items={blocks}
          getKey={b => b.id}
          onReorder={handleReorder}
          disabled={false}
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
    </>
  )
}
