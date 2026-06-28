import { useState } from 'react'
import { useParams, useNavigate } from 'react-router-dom'
import { Trash2 } from 'lucide-react'
import { PageHeader } from '@/components/ui/page-header'
import { Card } from '@/components/ui/card'
import { TypeBadge } from '@/components/ui/type-badge'
import { InlineEdit } from '@/components/ui/inline-edit'
import { ConfirmDialog } from '@/components/ui/confirm-dialog'
import { useExercise } from '@/hooks/use-exercises'
import { useEquipment } from '@/hooks/use-equipment'
import { useWorkouts } from '@/hooks/use-workouts'
import { useApp } from '@/lib/use-app'
import { equipmentName, exerciseUsageCount } from '@/lib/domain'
import { formatDuration } from '@/lib/formatters'

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
  const { data: ex } = useExercise(id || '')
  const { data: equipment } = useEquipment()
  const { data: workouts } = useWorkouts()
  const { updateExercise, deleteExercise, toast, user } = useApp()
  const navigate = useNavigate()
  const [deleting, setDeleting] = useState(false)

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

  const usage = exerciseUsageCount(workouts, ex.id)
  const inUse = usage > 0

  const attrs: Array<[string, string]> = []
  if (ex.type === 'resistance') {
    attrs.push(['Bodyweight base', ex.bodyweightBase ? 'Yes' : 'No'])
    attrs.push(['Allows added weight', ex.allowsAddedWeight ? 'Yes' : 'No'])
    attrs.push(['Bilateral', ex.bilateral ? 'Yes' : 'No (single-arm/leg)'])
  } else if (ex.type === 'distance') {
    attrs.push(['Distance unit', user.measurementSystem === 'metric' ? 'Kilometers' : 'Miles'])
    attrs.push(['Tracks elevation', ex.tracksElevation ? 'Yes' : 'No'])
  } else if (ex.type === 'interval') {
    attrs.push(['Default work', formatDuration(ex.defaultWorkSeconds)])
    attrs.push(['Default rest', formatDuration(ex.defaultRestSeconds)])
    attrs.push(['Default rounds', String(ex.defaultRounds || '—')])
  }

  return (
    <>
      <PageHeader
        back
        onBack={() => navigate('/exercises')}
        title={
          <InlineEdit
            value={ex.name}
            onChange={v => updateExercise(ex.id, { name: v })}
            ariaLabel="Exercise name"
          />
        }
      />

      <div className="flex items-center gap-2 mb-5">
        <TypeBadge type={ex.type} />
        <span className="text-sm text-text-secondary inline-flex items-center gap-1.5">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor">
            <path d="M7 4h10v2H7V4zm-1 6h1v7H6v-7zm3 0h1v7H9v-7zm3 0h1v7h-1v-7zm3 0h1v7h-1v-7zm3 0h1v7h-1v-7zm1-2c1.1 0 2-.9 2-2h-2c0 1.1.9 2 2 2s2-.9 2-2h-2c0 1.1.9 2 2 2s2-.9 2-2h2c0 1.1-.9 2-2 2h-2V4z" />
          </svg>
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

      <div className="flex items-center gap-2">
        <button
          onClick={() => {
            if (!inUse) setDeleting(true)
          }}
          disabled={inUse}
          title={
            inUse
              ? `In use by ${usage} workout${usage === 1 ? '' : 's'} and cannot be deleted.`
              : 'Delete exercise'
          }
          className={`inline-flex items-center gap-2 text-sm font-semibold px-3 h-11 rounded-lg ${
            inUse
              ? 'text-text-muted opacity-50 cursor-not-allowed'
              : 'text-destructive hover:bg-surface-muted'
          }`}
        >
          <Trash2 size={17} /> Delete
        </button>
        {inUse && (
          <span className="text-[12px] text-text-muted">
            In use by {usage} workout{usage === 1 ? '' : 's'}
          </span>
        )}
      </div>

      <ConfirmDialog
        open={deleting}
        title="Delete exercise?"
        message={`"${ex.name}" will be removed from your catalog.`}
        onCancel={() => setDeleting(false)}
        onConfirm={() => {
          deleteExercise(ex.id)
          toast('Exercise deleted.')
          navigate('/exercises')
        }}
      />
    </>
  )
}
