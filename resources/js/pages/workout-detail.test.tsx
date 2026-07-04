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

    // Wait for the group name "Superset A" to render
    const groupName = await screen.findByText('Superset A')
    expect(groupName).toBeInTheDocument()

    // The fixture shows a superset with planned_rounds: 3
    // Verify that "Round" text appears indicating multi-round group
    const roundIndicator = await screen.findByText(/Round \d+ of \d+/)
    expect(roundIndicator).toBeInTheDocument()
  })

  it('displays date picker and completion status', async () => {
    renderWithProviders(<WorkoutDetailPage />, {
      path: 'workouts/:id',
      route: '/workouts/43',
    })

    // Verify the date input is rendered with the fixture date
    const dateInput = await screen.findByDisplayValue('2026-06-01')
    expect(dateInput).toBeInTheDocument()

    // Verify the completion percentage text appears
    const completionText = await screen.findByText(/% complete/)
    expect(completionText).toBeInTheDocument()

    // Verify "Add Exercise" button is present
    const addExerciseBtn = await screen.findByRole('button', { name: /add exercise/i })
    expect(addExerciseBtn).toBeInTheDocument()
  })

  it('edits workout name via inline edit and persists to API', async () => {
    let capturedPutBody: Record<string, unknown> | null = null

    server.use(
      http.put('/api/v1/workouts/:id', async ({ request }) => {
        const body = await request.json()
        capturedPutBody = body as Record<string, unknown>
        // Return the updated workout with new name
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

    // Wait for the original workout name to render
    const nameButton = await screen.findByText('Capture Test Show Workout')
    expect(nameButton).toBeInTheDocument()

    // Click to enter edit mode
    await userEvent.click(nameButton)

    // Find the input that appears in edit mode
    const input = await screen.findByDisplayValue('Capture Test Show Workout')
    expect(input).toBeInTheDocument()

    // Clear and type new name
    await userEvent.tripleClick(input)
    await userEvent.keyboard('Updated Workout Name')

    // Blur to commit the change
    await userEvent.tab()

    // Wait for the API call to complete
    await waitFor(() => {
      expect(capturedPutBody).toEqual({ name: 'Updated Workout Name' })
    })

    // Verify the new name appears in the UI
    const updatedName = await screen.findByText('Updated Workout Name')
    expect(updatedName).toBeInTheDocument()
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

// Out of scope for this first pass: drag-and-drop reordering via dnd-kit (requires complex setup
// of draggable/droppable contexts) and debounced metric save flow (requires fake timers and
// testing pendingUpdates ref timing).
