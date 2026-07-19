import { describe, it, expect, vi } from 'vitest'
import { http, HttpResponse } from 'msw'
import { QueryClient, QueryClientProvider, useInfiniteQuery } from '@tanstack/react-query'
import { MemoryRouter, Routes, Route } from 'react-router-dom'
import { render, fireEvent, act } from '@testing-library/react'
import { screen, renderWithProviders, userEvent, waitFor, testUser } from '@/test/render'
import { server } from '@/test/server'
import { AppProvider } from '@/lib/store'
import { AuthContext } from '@/lib/auth-context'
import { workoutQueries } from '@/api/workouts'
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

  it('does not mark rounds without entries as completed', async () => {
    renderWithProviders(<WorkoutDetailPage />, {
      path: 'workouts/:id',
      route: '/workouts/43',
    })

    const groupBlock = await screen.findByText('Superset A')
    const groupCard = groupBlock.closest('[dusk="entry-group"]')
    expect(groupCard).toBeTruthy()

    // The fixture has planned_rounds=3 but only a round-1 entry with actuals.
    // Rounds 2 and 3 have no entries and must not render as completed.
    const round2Label = screen.getByText('Round 2')
    const round2Section = round2Label.closest('div')
    expect(round2Section).toBeTruthy()
    expect(round2Section?.querySelector('svg')).toBeNull()

    const round3Label = screen.getByText('Round 3')
    const round3Section = round3Label.closest('div')
    expect(round3Section).toBeTruthy()
    expect(round3Section?.querySelector('svg')).toBeNull()
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
    let detached = false
    server.use(
      http.get('/api/v1/workouts/:id', () => {
        if (detached) {
          return HttpResponse.json({
            data: {
              ...twoExerciseWorkout.data,
              exercises: twoExerciseWorkout.data.exercises.filter(e => e.id !== 1),
              entries: twoExerciseWorkout.data.entries.filter(e => e.exercise_id !== 1),
            },
          })
        }
        return HttpResponse.json(twoExerciseWorkout)
      }),
      http.delete('/api/v1/workouts/:id/exercises/:exerciseId', ({ request }) => {
        deletedUrl = new URL(request.url).pathname
        detached = true
        return new HttpResponse(null, { status: 204 })
      })
    )
    renderWithProviders(<WorkoutDetailPage />, {
      path: 'workouts/:id',
      route: '/workouts/43',
    })

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

describe('entry update error handling', () => {
  it('shows error toast and refetches cache when entry PUT fails', { timeout: 15000 }, async () => {
    server.use(
      http.get('/api/v1/workouts/:id', () => HttpResponse.json(twoExerciseWorkout)),
      http.put('/api/v1/workouts/:id/entries/:entryId', () => {
        return HttpResponse.json({ message: 'Server error' }, { status: 500 })
      })
    )
    renderWithProviders(<WorkoutDetailPage />, {
      path: 'workouts/:id',
      route: '/workouts/43',
    })

    // The second entry (Ab Crunch Machine) has actual_reps: 10
    const repsInputs = await screen.findAllByDisplayValue('10')
    const repsInput = repsInputs[0]
    if (!repsInput) throw new Error('Reps input not found')
    fireEvent.change(repsInput, { target: { value: '15' } })

    // The 800ms debounce fires, PUT returns 500, onError toasts and invalidates
    await waitFor(
      () => {
        expect(screen.getByText('Could not save. Try again.')).toBeInTheDocument()
      },
      { timeout: 5000 }
    )

    // After error, invalidation refetches and original value (10) returns
    await waitFor(() => {
      expect(screen.getAllByDisplayValue('10').length).toBeGreaterThan(0)
    })
  })

  it(
    'flushes pending entry updates through the mutation on unmount',
    { timeout: 15000 },
    async () => {
      let putFired = false
      server.use(
        http.get('/api/v1/workouts/:id', () => HttpResponse.json(twoExerciseWorkout)),
        http.put('/api/v1/workouts/:id/entries/:entryId', () => {
          putFired = true
          return HttpResponse.json({
            data: twoExerciseWorkout.data.entries[1],
          })
        })
      )
      const { unmount } = renderWithProviders(<WorkoutDetailPage />, {
        path: 'workouts/:id',
        route: '/workouts/43',
      })

      const repsInputs = await screen.findAllByDisplayValue('10')
      const repsInput = repsInputs[0]
      if (!repsInput) throw new Error('Reps input not found')
      await userEvent.clear(repsInput)
      await userEvent.type(repsInput, '12')

      // Unmount before the 800ms debounce fires
      unmount()

      // The flush-on-unmount should have fired the PUT
      await waitFor(() => {
        expect(putFired).toBe(true)
      })
    }
  )

  it('respects the debounce interval before firing entry updates', { timeout: 15000 }, async () => {
    let putCount = 0
    server.use(
      http.get('/api/v1/workouts/:id', () => HttpResponse.json(twoExerciseWorkout)),
      http.put('/api/v1/workouts/:id/entries/:entryId', () => {
        putCount++
        return HttpResponse.json({ data: twoExerciseWorkout.data.entries[1] })
      })
    )

    renderWithProviders(<WorkoutDetailPage />, {
      path: 'workouts/:id',
      route: '/workouts/43',
    })

    // Settle the initial load on real timers; findAllBy* is act-aware, so
    // mount-time updates (query data, Radix, dnd-kit) flush inside the test
    await screen.findAllByDisplayValue('10')

    // Reset after initial render so we only count PUTs from the change
    putCount = 0

    // Fake timers only for the debounce window itself
    vi.useFakeTimers()
    try {
      const repsInput = screen.getAllByDisplayValue('10')[0]
      if (!repsInput) throw new Error('Reps input not found')
      fireEvent.change(repsInput, { target: { value: '15' } })

      // Well under the 800ms debounce: no PUT should have fired
      await act(async () => {
        await vi.advanceTimersByTimeAsync(400)
      })
      expect(putCount).toBe(0)

      // Past the 800ms debounce: exactly one PUT
      await act(async () => {
        await vi.advanceTimersByTimeAsync(401)
      })
      expect(putCount).toBe(1)

      // Drain the PUT response so the onSuccess cache write lands inside the test
      await act(async () => {
        await vi.runAllTimersAsync()
      })
    } finally {
      vi.useRealTimers()
    }

    // Settled end state: the echoed server entry (reps 10) has replaced the
    // optimistic 15 in the cache, so nothing is left in flight after the test
    await waitFor(() => {
      expect(screen.queryByDisplayValue('15')).not.toBeInTheDocument()
    })
  })
})

// Subscribes to the workouts list query so invalidation triggers a refetch
function ListObserver() {
  useInfiniteQuery(workoutQueries.list())
  return null
}

function renderDetailWithListObserver(workoutId: string) {
  const queryClient = new QueryClient({
    defaultOptions: { queries: { retry: false }, mutations: { retry: false } },
  })
  const auth = {
    user: testUser,
    isLoading: false,
    setUser: vi.fn(),
    handleLogout: vi.fn(),
  }
  return render(
    <QueryClientProvider client={queryClient}>
      <MemoryRouter initialEntries={[`/workouts/${workoutId}`]}>
        <AuthContext.Provider value={auth}>
          <AppProvider>
            <ListObserver />
            <Routes>
              <Route path="workouts/:id" element={<WorkoutDetailPage />} />
            </Routes>
          </AppProvider>
        </AuthContext.Provider>
      </MemoryRouter>
    </QueryClientProvider>
  )
}

describe('list cache invalidation', () => {
  it('invalidates the workouts list after a workout name edit', { timeout: 15000 }, async () => {
    let listFetchCount = 0
    server.use(
      http.get('/api/v1/workouts', () => {
        listFetchCount++
        return HttpResponse.json({
          data: [],
          meta: { current_page: 1, last_page: 1, total: 0 },
        })
      }),
      http.get('/api/v1/workouts/:id', () => HttpResponse.json(twoExerciseWorkout)),
      http.put('/api/v1/workouts/:id', async ({ request }) => {
        await request.json()
        return HttpResponse.json({
          data: {
            ...twoExerciseWorkout.data,
            name: 'Renamed Workout',
          },
        })
      })
    )
    renderDetailWithListObserver('43')

    await screen.findByText('Test Workout')
    const fetchesBeforeEdit = listFetchCount

    const nameButton = screen.getByText('Test Workout')
    await userEvent.click(nameButton)
    const input = await screen.findByDisplayValue('Test Workout')
    await userEvent.tripleClick(input)
    await userEvent.keyboard('Renamed Workout')
    await userEvent.tab()

    await waitFor(() => {
      expect(listFetchCount).toBeGreaterThan(fetchesBeforeEdit)
    })
  })

  it('invalidates the workouts list after detaching an exercise', { timeout: 15000 }, async () => {
    let listFetchCount = 0
    let detached = false
    server.use(
      http.get('/api/v1/workouts', () => {
        listFetchCount++
        return HttpResponse.json({
          data: [],
          meta: { current_page: 1, last_page: 1, total: 0 },
        })
      }),
      http.get('/api/v1/workouts/:id', () => {
        if (detached) {
          return HttpResponse.json({
            data: {
              ...twoExerciseWorkout.data,
              exercises: twoExerciseWorkout.data.exercises.filter(e => e.id !== 1),
              entries: twoExerciseWorkout.data.entries.filter(e => e.exercise_id !== 1),
            },
          })
        }
        return HttpResponse.json(twoExerciseWorkout)
      }),
      http.delete('/api/v1/workouts/:id/exercises/:exerciseId', () => {
        detached = true
        return new HttpResponse(null, { status: 204 })
      })
    )
    renderDetailWithListObserver('43')

    await screen.findByText('3/4 Sit-Up')
    const fetchesBeforeDetach = listFetchCount

    const [button] = await screen.findAllByRole('button', { name: 'Remove exercise' })
    if (button) await userEvent.click(button)
    const removeBtn = await screen.findByRole('button', { name: 'Remove' })
    await userEvent.click(removeBtn)

    await waitFor(() => {
      expect(listFetchCount).toBeGreaterThan(fetchesBeforeDetach)
    })
  })

  it('invalidates the workouts list after an entry metric edit', { timeout: 15000 }, async () => {
    let listFetchCount = 0
    server.use(
      http.get('/api/v1/workouts', () => {
        listFetchCount++
        return HttpResponse.json({
          data: [],
          meta: { current_page: 1, last_page: 1, total: 0 },
        })
      }),
      http.get('/api/v1/workouts/:id', () => HttpResponse.json(twoExerciseWorkout)),
      http.put('/api/v1/workouts/:id/entries/:entryId', () => {
        return HttpResponse.json({ data: twoExerciseWorkout.data.entries[1] })
      })
    )
    renderDetailWithListObserver('43')

    await screen.findByText('Test Workout')
    const fetchesBeforeEdit = listFetchCount

    const repsInputs = screen.getAllByDisplayValue('10')
    const repsInput = repsInputs[0]
    if (!repsInput) throw new Error('Reps input not found')
    fireEvent.change(repsInput, { target: { value: '15' } })

    await waitFor(
      () => {
        expect(listFetchCount).toBeGreaterThan(fetchesBeforeEdit)
      },
      { timeout: 5000 }
    )
  })
})

describe('delete workout dialog', () => {
  it(
    'closes the dialog on confirm so a rapid second click cannot fire a duplicate DELETE',
    { timeout: 15000 },
    async () => {
      let deleteCount = 0
      server.use(
        http.get('/api/v1/workouts/:id', () => HttpResponse.json(twoExerciseWorkout)),
        http.delete('/api/v1/workouts/:id', () => {
          deleteCount++
          return new HttpResponse(null, { status: 204 })
        })
      )
      renderWithProviders(<WorkoutDetailPage />, {
        path: 'workouts/:id',
        route: '/workouts/43',
        additionalRoutes: [{ path: 'workouts', element: <div>Workouts list</div> }],
      })

      const deleteBtn = await screen.findByRole('button', { name: 'Delete workout' })
      await userEvent.click(deleteBtn)

      expect(await screen.findByText('Delete workout?')).toBeInTheDocument()

      const confirmBtn = await screen.findByRole('button', { name: 'Delete' })
      await userEvent.click(confirmBtn)

      await waitFor(() => {
        expect(screen.queryByText('Delete workout?')).not.toBeInTheDocument()
      })

      await waitFor(() => {
        expect(deleteCount).toBe(1)
      })
    }
  )
})

describe('delete workout does not refetch detail', () => {
  it(
    'removes the detail query before invalidating so no 404 GET fires',
    { timeout: 15000 },
    async () => {
      let deleted = false
      let postDeleteGetCount = 0
      server.use(
        http.get('/api/v1/workouts/:id', () => {
          if (deleted) {
            postDeleteGetCount++
            return HttpResponse.json({ message: 'Not found' }, { status: 404 })
          }
          return HttpResponse.json(twoExerciseWorkout)
        }),
        http.delete('/api/v1/workouts/:id', () => {
          deleted = true
          return new HttpResponse(null, { status: 204 })
        })
      )
      renderWithProviders(<WorkoutDetailPage />, {
        path: 'workouts/:id',
        route: '/workouts/43',
        additionalRoutes: [{ path: 'workouts', element: <div>Workouts list</div> }],
      })

      await screen.findByText('Test Workout')

      const deleteBtn = await screen.findByRole('button', { name: 'Delete workout' })
      await userEvent.click(deleteBtn)

      const confirmBtn = await screen.findByRole('button', { name: 'Delete' })
      await userEvent.click(confirmBtn)

      await waitFor(() => {
        expect(screen.getByText('Workouts list')).toBeInTheDocument()
      })

      expect(postDeleteGetCount).toBe(0)
    }
  )
})

describe('remove group', () => {
  it(
    'sends DELETE with delete_entries and closes dialog on confirm',
    { timeout: 15000 },
    async () => {
      let deletedUrl: string | null = null
      let deleteCount = 0
      server.use(
        http.delete('/api/v1/workouts/:id/groups/:groupId', ({ request }) => {
          const url = new URL(request.url)
          deletedUrl = url.pathname + url.search
          deleteCount++
          return new HttpResponse(null, { status: 204 })
        })
      )

      renderWithProviders(<WorkoutDetailPage />, {
        path: 'workouts/:id',
        route: '/workouts/43',
      })

      const removeGroupBtn = await screen.findByRole('button', { name: 'Remove group' })
      await userEvent.click(removeGroupBtn)

      expect(
        await screen.findByText(
          'All exercises and logged sets in this group will be removed from this workout.'
        )
      ).toBeInTheDocument()

      const confirmBtn = await screen.findByRole('button', { name: 'Remove' })
      await userEvent.click(confirmBtn)

      await waitFor(() => {
        expect(screen.queryByText('Remove superset?')).not.toBeInTheDocument()
      })

      await waitFor(() => {
        expect(deletedUrl).toBe('/api/v1/workouts/43/groups/1?delete_entries=1')
      })

      expect(deleteCount).toBe(1)
    }
  )
})
