import { describe, it, expect } from 'vitest'
import { http, HttpResponse } from 'msw'
import { renderWithProviders, screen, userEvent, waitFor } from '@/test/render'
import { server } from '@/test/server'
import { ExercisesPage } from './exercises'
import fixtureList from '@/test/mocks/fixtures/exercises/list.json'
import type { Exercise } from '@/api/types'

describe('ExercisesPage', () => {
  describe('List renders from fixture', () => {
    it('displays exercises from the fixture with correct count in subtitle', async () => {
      renderWithProviders(<ExercisesPage />)

      await screen.findByText('3/4 Sit-Up')

      const exerciseCount = fixtureList.data.length
      expect(screen.getByText(`${exerciseCount} in your catalog`)).toBeInTheDocument()
    })
  })

  describe('Create flow', () => {
    it('posts the correct payload when creating an exercise', async () => {
      // No inter-key delay: each keystroke re-renders the whole page, which
      // overruns the test timeout on a loaded host.
      const user = userEvent.setup({ delay: null })
      let capturedPayload: Record<string, unknown> | null = null

      server.use(
        // A five-row list keeps interaction re-renders cheap; the full
        // 900-row fixture is the list-render test's contract, not this one's.
        http.get('/api/v1/exercises', () =>
          HttpResponse.json({ data: fixtureList.data.slice(0, 5) })
        ),
        http.post('/api/v1/exercises', async ({ request }) => {
          capturedPayload = (await request.json()) as Record<string, unknown>
          return HttpResponse.json(
            {
              data: {
                id: 999,
                name: 'Incline Press',
                type: 'resistance',
                notes: null,
                user_id: 1,
                equipment_type_id: null,
                equipment_type: null,
                type_attributes: {
                  bodyweight_base: false,
                  allows_added_weight: true,
                  bilateral: true,
                },
                created_at: '2026-06-30T00:00:00.000000Z',
                updated_at: '2026-06-30T00:00:00.000000Z',
                usage_count: 0,
                has_logged_data: false,
              },
            },
            { status: 201 }
          )
        })
      )

      renderWithProviders(<ExercisesPage />)

      await screen.findByText('3/4 Sit-Up')

      const createButton = screen.getByRole('button', { name: 'Create' })
      await user.click(createButton)

      const nameInput = screen.getByLabelText('Name')
      await user.type(nameInput, 'Incline Press')

      const createExerciseButton = screen.getByRole('button', { name: 'Create Exercise' })
      await user.click(createExerciseButton)

      await waitFor(() => {
        expect(capturedPayload).toBeTruthy()
      })

      expect(capturedPayload).toEqual({
        name: 'Incline Press',
        type: 'resistance',
        equipment_type_id: null,
        notes: null,
        type_attributes: {
          bodyweight_base: false,
          allows_added_weight: true,
          bilateral: true,
        },
      })

      expect(await screen.findByText('Exercise created.')).toBeInTheDocument()
    }, 15000)
  })

  describe('409 in-use delete path', () => {
    it('shows the correct error message when deleting an exercise in use', async () => {
      const user = userEvent.setup()
      let deleteCalled = false

      const userExercise: Exercise = {
        id: '999',
        name: 'Test User Exercise',
        type: 'resistance',
        notes: null,
        userId: '1',
        equipmentTypeId: null,
        bodyweightBase: false,
        allowsAddedWeight: true,
        bilateral: true,
        usageCount: 0,
        hasLoggedData: false,
      }

      server.use(
        http.get('/api/v1/exercises', () =>
          HttpResponse.json({
            data: [...fixtureList.data.slice(0, 5), userExercise],
          })
        ),
        http.delete('/api/v1/exercises/:id', ({ params }) => {
          deleteCalled = true
          if (params.id === '999') {
            return HttpResponse.json(
              { message: 'Exercise is in use by 2 workout(s).' },
              { status: 409 }
            )
          }
          return new HttpResponse(null, { status: 204 })
        })
      )

      renderWithProviders(<ExercisesPage />)

      await screen.findByText('3/4 Sit-Up')
      await screen.findByText('Test User Exercise')

      const deleteButton = screen.getByLabelText('Delete Test User Exercise')
      await user.click(deleteButton)

      // The confirm dialog reuses the "Delete" label, so target the last one.
      const confirmButtons = screen.getAllByRole('button', { name: 'Delete' })
      const dialogConfirmButton = confirmButtons[confirmButtons.length - 1]
      expect(dialogConfirmButton).toBeDefined()
      if (dialogConfirmButton) {
        await user.click(dialogConfirmButton)
      }

      await waitFor(() => {
        expect(deleteCalled).toBe(true)
      })

      expect(await screen.findByText('Exercise is in use by 2 workout(s).')).toBeInTheDocument()
    }, 15000)
  })
})
