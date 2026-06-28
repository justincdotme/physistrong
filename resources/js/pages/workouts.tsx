import { useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { Plus, BarChart3 } from 'lucide-react'
import type { useWorkout } from '@/hooks/use-workouts'
import { useWorkouts } from '@/hooks/use-workouts'
import { useExercises } from '@/hooks/use-exercises'
import { useApp } from '@/lib/store'
import { formatDate } from '@/lib/formatters'
import { workoutCompletion, exerciseById } from '@/lib/domain'
import {
  PageHeader,
  SectionHeading,
  EmptyState,
  CompletionBar,
  Button,
  Card,
} from '@/components/ui'
import { NewWorkoutWizard } from '@/components/app/pickers'

interface WorkoutCardProps {
  workout: ReturnType<typeof useWorkout>['data']
  exercises: ReturnType<typeof useExercises>['data']
}

function WorkoutCard({ workout, exercises }: WorkoutCardProps) {
  const navigate = useNavigate()
  if (!workout) return null

  const ratio = workoutCompletion(workout)
  const names: string[] = []
  const seen = new Set<string>()

  workout.entries.forEach(e => {
    if (!seen.has(e.exerciseId)) {
      seen.add(e.exerciseId)
      const ex = exerciseById(exercises, e.exerciseId)
      if (ex) names.push(ex.name)
    }
  })

  const shown = names.slice(0, 4)
  const extra = names.length - shown.length
  const complete = ratio >= 1

  return (
    <Card
      onClick={() => navigate(`/workouts/${workout.id}`)}
      className="overflow-hidden cursor-pointer"
    >
      <div className="p-4">
        <div className="flex items-center justify-between mb-1">
          <span className="label-caps text-text-muted">{formatDate(workout.date)}</span>
          {complete ? (
            <span
              className="inline-flex items-center gap-1 text-[11px] font-semibold"
              style={{ color: 'var(--color-success)' }}
            >
              ✓ Complete
            </span>
          ) : (
            <span className="text-[11px] font-semibold text-text-muted">
              {Math.round(ratio * 100)}%
            </span>
          )}
        </div>
        <div className="font-semibold text-base mb-1.5">{workout.name}</div>
        <p className="text-[13px] text-text-secondary">
          {shown.join(' · ')}
          {extra > 0 ? ` · +${extra} more` : ''}
        </p>
      </div>
      <CompletionBar ratio={ratio} height={4} />
    </Card>
  )
}

export function WorkoutsPage() {
  const navigate = useNavigate()
  const { data: workouts } = useWorkouts()
  const { data: exercises } = useExercises()
  const { addWorkout, createFromTemplate, toast } = useApp()

  const [wizardOpen, setWizardOpen] = useState(false)

  const startEmpty = ({ name, date }: { name: string; date: string }) => {
    const w = addWorkout({ name, date, entries: [], entryGroups: [] })
    toast('Workout started.')
    navigate(`/workouts/${w.id}`)
  }

  const startFromTemplate = (
    tpl: { id: string },
    { name, date }: { name: string; date: string }
  ) => {
    const w = createFromTemplate(tpl.id, date, name || undefined)
    if (w) {
      toast('Workout started.')
      navigate(`/workouts/${w.id}`)
    }
  }

  return (
    <>
      <PageHeader title="Workouts" subtitle={`${workouts.length} logged`} />

      <div className="flex flex-col gap-2.5 mb-7">
        <Button full size="lg" icon={<Plus size={18} />} onClick={() => setWizardOpen(true)}>
          New Workout
        </Button>
        <Button
          full
          variant="secondary"
          icon={<BarChart3 size={18} />}
          onClick={() => navigate('/progress')}
        >
          View Progress
        </Button>
      </div>

      <section>
        <SectionHeading>History</SectionHeading>
        {workouts.length ? (
          <div className="flex flex-col gap-3">
            {workouts.map(w => (
              <WorkoutCard key={w.id} workout={w} exercises={exercises} />
            ))}
          </div>
        ) : (
          <EmptyState
            icon="dumbbell"
            title="Ready to train?"
            action={
              <Button onClick={() => setWizardOpen(true)} icon={<Plus size={18} />}>
                New Workout
              </Button>
            }
          >
            Create your first workout or set up a template.
          </EmptyState>
        )}
      </section>

      <NewWorkoutWizard
        open={wizardOpen}
        onClose={() => setWizardOpen(false)}
        onCreateEmpty={startEmpty}
        onCreateFromTemplate={startFromTemplate}
      />
    </>
  )
}
