import { useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { useInfiniteQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { Plus, BarChart3, Layers, Check } from 'lucide-react'
import type { WorkoutListItem } from '@/api/types'
import { listWorkouts, createWorkout } from '@/api/workouts'
import { useApp } from '@/lib/use-app'
import { formatDate } from '@/lib/formatters'
import {
  PageHeader,
  SectionHeading,
  EmptyState,
  CompletionBar,
  Button,
  Card,
} from '@/components/ui'
import { NewWorkoutWizard } from '@/components/app/pickers'

function WorkoutCard({ workout }: { workout: WorkoutListItem }) {
  const navigate = useNavigate()

  const ratio = workout.entriesCount > 0 ? workout.completedEntriesCount / workout.entriesCount : 0
  const names = workout.exercises.map(e => e.name)
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
              <Check size={13} /> Complete
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
  const queryClient = useQueryClient()
  const { toast } = useApp()

  const [wizardOpen, setWizardOpen] = useState(false)

  const { data, isLoading, hasNextPage, fetchNextPage, isFetchingNextPage } = useInfiniteQuery({
    queryKey: ['workouts'],
    queryFn: ({ pageParam }) => listWorkouts(pageParam),
    getNextPageParam: last => last.nextPage,
    initialPageParam: 1,
  })

  const workouts = data?.pages?.flatMap(p => p.items) ?? []
  const total = data?.pages?.[0]?.total ?? 0

  const createMutation = useMutation({
    mutationFn: createWorkout,
    onSuccess: workout => {
      queryClient.invalidateQueries({ queryKey: ['workouts'] })
      toast('Workout started.')
      navigate(`/workouts/${workout.id}`)
    },
    onError: () => toast('Could not create workout. Try again.', 'error'),
  })

  const startEmpty = ({ name, date }: { name: string; date: string }) => {
    createMutation.mutate({ name, date })
  }

  if (isLoading) {
    return (
      <>
        <PageHeader title="Workouts" />
        <p className="text-text-secondary text-sm">Loading...</p>
      </>
    )
  }

  return (
    <div dusk="workouts-page">
      <PageHeader title="Workouts" subtitle={`${total} logged`} />

      <div className="flex flex-col gap-2.5 mb-7">
        <Button
          full
          size="lg"
          icon={<Plus size={18} />}
          onClick={() => setWizardOpen(true)}
          dusk="create-workout-btn"
        >
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
        <Button
          full
          variant="secondary"
          icon={<Layers size={18} />}
          onClick={() => navigate('/templates')}
          dusk="templates-link"
        >
          Templates
        </Button>
      </div>

      <section>
        <SectionHeading>History</SectionHeading>
        {workouts.length ? (
          <div className="flex flex-col gap-3" dusk="workout-history">
            {workouts.map(w => (
              <WorkoutCard key={w.id} workout={w} />
            ))}
            {hasNextPage && (
              <Button
                full
                variant="secondary"
                onClick={() => fetchNextPage()}
                disabled={isFetchingNextPage}
                className="mt-3"
              >
                {isFetchingNextPage ? 'Loading...' : 'Load More'}
              </Button>
            )}
          </div>
        ) : (
          <div dusk="workouts-empty">
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
          </div>
        )}
      </section>

      <NewWorkoutWizard
        open={wizardOpen}
        onClose={() => setWizardOpen(false)}
        onCreateEmpty={startEmpty}
        onCloneSuccess={workoutId => {
          setWizardOpen(false)
          queryClient.invalidateQueries({ queryKey: ['workouts'] })
          toast('Workout started from template.')
          navigate(`/workouts/${workoutId}`)
        }}
        onCopySuccess={workoutId => {
          setWizardOpen(false)
          queryClient.invalidateQueries({ queryKey: ['workouts'] })
          toast('Workout copied.')
          navigate(`/workouts/${workoutId}`)
        }}
      />
    </div>
  )
}
