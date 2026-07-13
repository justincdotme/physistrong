import { describe, it, expect } from 'vitest'
import { http, HttpResponse } from 'msw'
import { screen, renderWithProviders, userEvent, waitFor } from '@/test/render'
import { server } from '@/test/server'
import { WorkoutDetailPage } from './workout-detail'
import { collectReorderExerciseIds } from './workout-detail.utils'

describe('WorkoutDetailPage', () => {
  it('renders grouped entries with group name and round indicator', async () => {
    renderWithProviders(<WorkoutDetailPage />, {
      path: 'workouts/:id',
      route: '/workouts/43',
    })

    const groupName = await screen.findByText('Superset A')
    expect(groupName).toBeInTheDocument()

    // The fixture shows a superset with planned_rounds: 3
    const roundIndicator = await screen.findByText(/Round \d+ of \d+/)
    expect(roundIndicator).toBeInTheDocument()
  })

  it('displays date picker and completion status', async () => {
    renderWithProviders(<WorkoutDetailPage />, {
      path: 'workouts/:id',
      route: '/workouts/43',
    })

    const dateInput = await screen.findByDisplayValue('2026-06-01')
    expect(dateInput).toBeInTheDocument()

    const completionText = await screen.findByText(/% complete/)
    expect(completionText).toBeInTheDocument()

    const addExerciseBtn = await screen.findByRole('button', { name: /add exercise/i })
    expect(addExerciseBtn).toBeInTheDocument()
  })

  it('edits workout name via inline edit and persists to API', async () => {
    let capturedPutBody: Record<string, unknown> | null = null

    server.use(
      http.put('/api/v1/workouts/:id', async ({ request }) => {
        const body = await request.json()
        capturedPutBody = body as Record<string, unknown>
        return HttpResponse.json({
          data: {
            id: 43,
            name: 'Updated Workout Name',
            date: '2026-06-01',
            exhaustion: null,
            soreness: null,
            exercises: [],
            groups: [],
            entries: [],
            created_at: '2026-01-01T00:00:00.000000Z',
            updated_at: '2026-01-01T00:00:00.000000Z',
          },
        })
      })
    )

    renderWithProviders(<WorkoutDetailPage />, {
      path: 'workouts/:id',
      route: '/workouts/43',
    })

    const nameButton = await screen.findByText('Capture Test Show Workout')
    expect(nameButton).toBeInTheDocument()

    await userEvent.click(nameButton)

    const input = await screen.findByDisplayValue('Capture Test Show Workout')
    expect(input).toBeInTheDocument()

    await userEvent.tripleClick(input)
    await userEvent.keyboard('Updated Workout Name')

    await userEvent.tab()

    await waitFor(() => {
      expect(capturedPutBody).toEqual({ name: 'Updated Workout Name' })
    })

    const updatedName = await screen.findByText('Updated Workout Name')
    expect(updatedName).toBeInTheDocument()
  })
})

const twoExerciseWorkout = {
  data: {
    id: 43,
    name: 'Test Workout',
    date: '2026-06-01',
    exhaustion: null,
    soreness: null,
    exercises: [
      {
        id: 1,
        name: '3/4 Sit-Up',
        type: 'resistance',
        equipment_type_id: null,
        exercise_order: 0,
      },
      {
        id: 3,
        name: 'Ab Crunch Machine',
        type: 'resistance',
        equipment_type_id: 4,
        exercise_order: 1,
      },
    ],
    groups: [],
    entries: [
      {
        id: 100,
        workout_id: 43,
        exercise_id: 1,
        set_order: 0,
        entry_group_id: null,
        group_round: null,
        notes: null,
        exercise: { id: 1, name: '3/4 Sit-Up', type: 'resistance' },
        metrics: {
          load: { target_weight: '135.00', actual_weight: '130.00', bodyweight_only: true },
          reps: { target_reps: 8, actual_reps: 8, to_failure: false, failure_rep: null },
        },
        created_at: '2026-01-01T00:00:00.000000Z',
        updated_at: '2026-01-01T00:00:00.000000Z',
      },
      {
        id: 101,
        workout_id: 43,
        exercise_id: 3,
        set_order: 1,
        entry_group_id: null,
        group_round: null,
        notes: null,
        exercise: { id: 3, name: 'Ab Crunch Machine', type: 'resistance' },
        metrics: {
          load: { target_weight: '100.00', actual_weight: '100.00', bodyweight_only: false },
          reps: { target_reps: 10, actual_reps: 10, to_failure: false, failure_rep: null },
        },
        created_at: '2026-01-01T00:00:00.000000Z',
        updated_at: '2026-01-01T00:00:00.000000Z',
      },
    ],
    created_at: '2026-01-01T00:00:00.000000Z',
    updated_at: '2026-01-01T00:00:00.000000Z',
  },
}

function renderWithTwoExercises() {
  server.use(http.get('/api/v1/workouts/:id', () => HttpResponse.json(twoExerciseWorkout)))
  return renderWithProviders(<WorkoutDetailPage />, {
    path: 'workouts/:id',
    route: '/workouts/43',
  })
}

describe('remove exercise', () => {
  async function clickFirstRemoveExercise() {
    const [button] = await screen.findAllByRole('button', { name: 'Remove exercise' })
    expect(button).toBeDefined()
    if (button) await userEvent.click(button)
  }

  it('renders a remove button on each exercise block', async () => {
    renderWithTwoExercises()

    const buttons = await screen.findAllByRole('button', { name: 'Remove exercise' })
    expect(buttons).toHaveLength(2)
  })

  it('shows a confirm dialog warning about logged sets', async () => {
    renderWithTwoExercises()

    await clickFirstRemoveExercise()

    expect(
      await screen.findByText('All logged sets for this exercise in this workout will be removed.')
    ).toBeInTheDocument()
  })

  it('does nothing when cancel is clicked', async () => {
    renderWithTwoExercises()

    await clickFirstRemoveExercise()

    const cancelBtn = await screen.findByRole('button', { name: 'Cancel' })
    await userEvent.click(cancelBtn)

    expect(screen.getByText('3/4 Sit-Up')).toBeInTheDocument()
    expect(screen.getByText('Ab Crunch Machine')).toBeInTheDocument()
  })

  it('calls DELETE and removes the exercise block on confirm', async () => {
    let deletedUrl: string | null = null
    server.use(
      http.delete('/api/v1/workouts/:id/exercises/:exerciseId', ({ request }) => {
        deletedUrl = new URL(request.url).pathname
        return new HttpResponse(null, { status: 204 })
      })
    )
    renderWithTwoExercises()

    await clickFirstRemoveExercise()

    const removeBtn = await screen.findByRole('button', { name: 'Remove' })
    await userEvent.click(removeBtn)

    await waitFor(() => {
      expect(deletedUrl).toBe('/api/v1/workouts/43/exercises/1')
    })

    await waitFor(() => {
      expect(screen.queryByText('3/4 Sit-Up')).not.toBeInTheDocument()
    })
    expect(screen.getByText('Ab Crunch Machine')).toBeInTheDocument()
  })
})

describe('collectReorderExerciseIds', () => {
  const block = (exerciseIds: string[]) => ({
    entries: exerciseIds.map(id => ({ exerciseId: id })),
  })

  it('collects exercise ids from blocks in visual order', () => {
    expect(collectReorderExerciseIds([block(['2']), block(['1'])], [])).toEqual(['2', '1'])
  })

  it('includes grouped exercises via their entries', () => {
    // A superset block carries entries for several exercises
    expect(collectReorderExerciseIds([block(['3', '4']), block(['1'])], [])).toEqual([
      '3',
      '4',
      '1',
    ])
  })

  it('appends attached exercises that have no visible block', () => {
    expect(collectReorderExerciseIds([block(['1'])], [{ id: '9' }, { id: '1' }])).toEqual([
      '1',
      '9',
    ])
  })

  it('dedupes to first occurrence when an exercise spans blocks', () => {
    expect(collectReorderExerciseIds([block(['1']), block(['2']), block(['1'])], [])).toEqual([
      '1',
      '2',
    ])
  })
})
