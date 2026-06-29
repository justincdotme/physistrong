import { useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { useInfiniteQuery, useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { Plus, BarChart3, Layers } from 'lucide-react'
import type { WorkoutListItem } from '@/api/types'
import { listWorkouts, createWorkout } from '@/api/workouts'
import { listTemplates, createTemplate } from '@/api/templates'
import { useApp } from '@/lib/use-app'
import { formatDate } from '@/lib/formatters'
import {
  PageHeader,
  SectionHeading,
  EmptyState,
  CompletionBar,
  Button,
  Card,
  Sheet,
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
  const queryClient = useQueryClient()
  const { toast } = useApp()

  const [wizardOpen, setWizardOpen] = useState(false)
  const [templateSheetOpen, setTemplateSheetOpen] = useState(false)
  const [templateName, setTemplateName] = useState('')

  const { data, isLoading, hasNextPage, fetchNextPage, isFetchingNextPage } = useInfiniteQuery({
    queryKey: ['workouts'],
    queryFn: ({ pageParam }) => listWorkouts(pageParam),
    getNextPageParam: last => last.nextPage,
    initialPageParam: 1,
  })

  const { data: templates = [] } = useQuery({
    queryKey: ['templates'],
    queryFn: listTemplates,
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
    onError: () => toast('Could not create workout. Try again.'),
  })

  const createTemplateMutation = useMutation({
    mutationFn: (name: string) => createTemplate({ name }),
    onSuccess: tpl => {
      queryClient.invalidateQueries({ queryKey: ['templates'] })
      setTemplateSheetOpen(false)
      setTemplateName('')
      toast('Template created.')
      navigate(`/templates/${tpl.id}`)
    },
    onError: () => toast('Could not create template. Try again.'),
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
          data-dusk="create-workout-btn"
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
          onClick={() => setTemplateSheetOpen(true)}
        >
          Create Template
        </Button>
      </div>

      {templates.length > 0 && (
        <section className="mb-7" dusk="template-section">
          <SectionHeading>Templates</SectionHeading>
          <div className="flex gap-3 overflow-x-auto pb-2 no-scrollbar">
            {templates.map(tpl => (
              <Card
                key={tpl.id}
                onClick={() => navigate(`/templates/${tpl.id}`)}
                className="flex-shrink-0 w-40 cursor-pointer"
              >
                <div className="p-3">
                  <div className="font-semibold text-sm truncate">{tpl.name}</div>
                  <div className="text-[12px] text-text-secondary">
                    {tpl.exercises.length} exercise{tpl.exercises.length !== 1 ? 's' : ''}
                  </div>
                </div>
              </Card>
            ))}
          </div>
        </section>
      )}

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
      />

      <Sheet
        open={templateSheetOpen}
        onClose={() => {
          setTemplateSheetOpen(false)
          setTemplateName('')
        }}
        title="Create Template"
        footer={
          <Button
            full
            disabled={!templateName.trim() || createTemplateMutation.isPending}
            onClick={() => createTemplateMutation.mutate(templateName.trim())}
          >
            {createTemplateMutation.isPending ? 'Creating...' : 'Create'}
          </Button>
        }
      >
        <div>
          <label
            htmlFor="template-name-input"
            className="label-caps text-text-secondary block mb-1.5"
          >
            Template name
          </label>
          <input
            id="template-name-input"
            value={templateName}
            onChange={e => setTemplateName(e.target.value)}
            placeholder="e.g. Push Day"
            className="ps-input w-full px-3 py-2.5 text-sm"
          />
        </div>
      </Sheet>
    </div>
  )
}
