import { useState } from 'react'
import { useParams, useNavigate } from 'react-router-dom'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { isAxiosError } from 'axios'
import { Trash2 } from 'lucide-react'
import { PageHeader } from '@/components/ui/page-header'
import { Card } from '@/components/ui/card'
import { TypeBadge } from '@/components/ui/type-badge'
import { InlineEdit } from '@/components/ui/inline-edit'
import { ConfirmDialog } from '@/components/ui/confirm-dialog'
import { useApp } from '@/lib/use-app'
import { equipmentName } from '@/lib/domain'
import { formatDuration } from '@/lib/formatters'
import {
  getExercise,
  updateExercise as updateExerciseApi,
  deleteExercise as deleteExerciseApi,
} from '@/api/exercises'
import { listEquipment } from '@/api/equipment'
import type { UpdateExercisePayload } from '@/api/exercises'

const DISTANCE_UNIT_LABELS: Record<string, string> = {
  meters: 'Meters',
  kilometers: 'Kilometers',
  miles: 'Miles',
  yards: 'Yards',
}

function AttrRow({ label, value }: { label: string; value: string }) {
  return (
    <div className="flex items-center justify-between py-3 border-b border-border last:border-0">
      <span className="text-sm text-text-secondary">{label}</span>
      <span className="text-sm font-semibold">{value}</span>
    </div>
  )
}

export function ExerciseDetailPage() {
  const { id } = useParams<{ id: string }>()
  const navigate = useNavigate()
  const queryClient = useQueryClient()
  const { toast } = useApp()
  const [deleting, setDeleting] = useState(false)

  const { data: ex, isLoading } = useQuery({
    queryKey: ['exercises', id],
    queryFn: () => {
      if (!id) throw new Error('Exercise ID is required')
      return getExercise(id)
    },
    enabled: !!id,
  })

  const { data: equipment = [] } = useQuery({
    queryKey: ['equipment'],
    queryFn: listEquipment,
  })

  const updateMutation = useMutation({
    mutationFn: (payload: UpdateExercisePayload) => {
      if (!id) throw new Error('Exercise ID is required')
      return updateExerciseApi(id, payload)
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['exercises'] })
      toast('Exercise updated.')
    },
    onError: () => {
      toast('Could not update. Try again.')
    },
  })

  const deleteMutation = useMutation({
    mutationFn: () => {
      if (!id) throw new Error('Exercise ID is required')
      return deleteExerciseApi(id)
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['exercises'] })
      toast('Exercise deleted.')
      navigate('/exercises')
    },
    onError: error => {
      if (isAxiosError(error) && error.response?.status === 409) {
        toast(error.response.data?.message ?? 'Exercise is in use.')
      } else {
        toast('Could not delete. Try again.')
      }
    },
  })

  if (isLoading) {
    return (
      <>
        <PageHeader back onBack={() => navigate('/exercises')} title="Loading..." />
        <p className="text-text-secondary text-sm">Loading exercise...</p>
      </>
    )
  }

  if (!ex) {
    return (
      <div className="py-16 text-center text-text-muted">
        Exercise not found.{' '}
        <button
          className="text-primary font-semibold"
          onClick={() => navigate('/exercises')}
          type="button"
        >
          Back
        </button>
      </div>
    )
  }

  const isOwned = ex.userId !== null
  const attrs: Array<[string, string]> = []

  if (ex.type === 'resistance') {
    attrs.push(['Bodyweight base', ex.bodyweightBase ? 'Yes' : 'No'])
    attrs.push(['Allows added weight', ex.allowsAddedWeight ? 'Yes' : 'No'])
    attrs.push(['Bilateral', ex.bilateral ? 'Yes' : 'No (single-arm/leg)'])
  } else if (ex.type === 'timed_hold') {
    if (ex.targetDurationSeconds) {
      attrs.push(['Target duration', formatDuration(ex.targetDurationSeconds)])
    }
  } else if (ex.type === 'distance') {
    attrs.push([
      'Distance unit',
      DISTANCE_UNIT_LABELS[ex.distanceUnit ?? ''] ?? ex.distanceUnit ?? '',
    ])
    attrs.push(['Tracks elevation', ex.tracksElevation ? 'Yes' : 'No'])
  } else if (ex.type === 'interval') {
    attrs.push(['Default work', formatDuration(ex.defaultWorkSeconds)])
    attrs.push(['Default rest', formatDuration(ex.defaultRestSeconds)])
    attrs.push(['Default rounds', String(ex.defaultRounds || '—')])
  }

  return (
    <div dusk="exercise-detail-page">
      <PageHeader
        back
        onBack={() => navigate('/exercises')}
        title={
          isOwned ? (
            <InlineEdit
              value={ex.name}
              onChange={v =>
                updateMutation.mutate({
                  name: v,
                  equipment_type_id: ex.equipmentTypeId ? Number(ex.equipmentTypeId) : null,
                  notes: ex.notes,
                })
              }
              ariaLabel="Exercise name"
            />
          ) : (
            <span dusk="exercise-name">{ex.name}</span>
          )
        }
      />

      <div className="flex items-center gap-2 mb-5">
        <div dusk="exercise-type">
          <TypeBadge type={ex.type} />
        </div>
        <span className="text-sm text-text-secondary">
          {equipmentName(equipment, ex.equipmentTypeId)}
        </span>
      </div>

      {ex.notes && <div className="ps-card p-4 mb-4 text-sm text-text-secondary">{ex.notes}</div>}

      {attrs.length > 0 && (
        <Card className="px-4 mb-4">
          <div className="py-1">
            <div className="label-caps text-text-muted pt-3 pb-1">Attributes</div>
            {attrs.map(([l, v]) => (
              <AttrRow key={l} label={l} value={v} />
            ))}
          </div>
        </Card>
      )}

      <button
        dusk="progress-link"
        onClick={() => navigate(`/exercises/${ex.id}/progress`)}
        className="ps-card w-full p-4 flex items-center gap-3 mb-6 hover:bg-surface-muted text-left"
      >
        <span
          className="h-10 w-10 rounded-lg flex items-center justify-center shrink-0"
          style={{
            background: 'color-mix(in srgb, var(--color-primary) 12%, transparent)',
            color: 'var(--color-primary)',
          }}
        >
          <svg
            width="20"
            height="20"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            strokeWidth="2"
          >
            <line x1="12" y1="5" x2="12" y2="19" />
            <polyline points="19 12 12 19 5 12" />
          </svg>
        </span>
        <div className="flex-1">
          <div className="font-semibold text-sm">View Progress</div>
          <div className="text-[12px] text-text-secondary">Charts, volume & personal records</div>
        </div>
        <svg
          width="18"
          height="18"
          viewBox="0 0 24 24"
          fill="none"
          stroke="currentColor"
          strokeWidth="2"
          className="text-text-muted"
        >
          <polyline points="9 18 15 12 9 6" />
        </svg>
      </button>

      {isOwned && (
        <>
          <div className="flex items-center gap-2">
            <button
              dusk="delete-exercise-btn"
              onClick={() => setDeleting(true)}
              className="inline-flex items-center gap-2 text-sm font-semibold px-3 h-11 rounded-lg text-destructive hover:bg-surface-muted"
            >
              <Trash2 size={17} /> Delete
            </button>
          </div>

          <ConfirmDialog
            open={deleting}
            title="Delete exercise?"
            message={`"${ex.name}" will be removed from your catalog.`}
            onCancel={() => setDeleting(false)}
            onConfirm={() => {
              setDeleting(false)
              deleteMutation.mutate()
            }}
          />
        </>
      )}
    </div>
  )
}
