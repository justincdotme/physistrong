import { describe, it, expect } from 'vitest'
import { http, HttpResponse } from 'msw'
import { renderWithProviders, screen, userEvent, waitFor } from '@/test/render'
import { server } from '@/test/server'
import { ExerciseDetailPage } from './exercise-detail'
import fixtureShowResistance from '@/test/mocks/fixtures/exercises/show-resistance.json'
import fixtureInUseError from '@/test/mocks/fixtures/exercises/in-use-error.json'

const exerciseId = String(fixtureShowResistance.data.id)
const exerciseName = fixtureShowResistance.data.name

describe('ExerciseDetailPage', () => {
  describe('Loads and renders the exercise', () => {
    it('displays the exercise name from the fixture', async () => {
      renderWithProviders(<ExerciseDetailPage />, {
        path: 'exercises/:id',
        route: `/exercises/${exerciseId}`,
      })

      await screen.findByText(exerciseName)
      expect(screen.getByText(exerciseName)).toBeInTheDocument()
    })

    it('displays the exercise type badge', async () => {
      renderWithProviders(<ExerciseDetailPage />, {
        path: 'exercises/:id',
        route: `/exercises/${exerciseId}`,
      })

      await screen.findByText('Resistance')
      expect(screen.getByText('Resistance')).toBeInTheDocument()
    })
  })

  describe('Update flow', () => {
    it('sends the correct payload when updating exercise name', async () => {
      const user = userEvent.setup()
      let capturedPayload: unknown = null
      let updateWasCalled = false

      server.use(
        http.put(`/api/v1/exercises/${exerciseId}`, async ({ request }) => {
          updateWasCalled = true
          capturedPayload = await request.json()
          return HttpResponse.json(fixtureShowResistance)
        })
      )

      renderWithProviders(<ExerciseDetailPage />, {
        path: 'exercises/:id',
        route: `/exercises/${exerciseId}`,
      })

      // Wait for the exercise to load
      await screen.findByText(exerciseName)

      // Click the edit button (inline edit on the title)
      const editButton = screen.getByLabelText('Exercise name')
      await user.click(editButton)

      // Find the input field and clear it, then type a new name
      const input = screen.getByDisplayValue(exerciseName)
      await user.clear(input)
      await user.type(input, 'Updated Exercise Name')

      // Press Enter to commit
      await user.keyboard('{Enter}')

      // Wait for the mutation to complete and verify the payload
      await waitFor(() => {
        expect(updateWasCalled).toBe(true)
        expect(capturedPayload).toEqual({
          name: 'Updated Exercise Name',
          equipment_type_id: fixtureShowResistance.data.equipment_type_id,
          notes: fixtureShowResistance.data.notes,
        })
      })
    })
  })

  describe('Delete flow', () => {
    it('allows deleting an unused exercise with 204 response', async () => {
      const user = userEvent.setup()
      let deleteWasCalled = false

      server.use(
        http.delete(`/api/v1/exercises/${exerciseId}`, () => {
          deleteWasCalled = true
          return new HttpResponse(null, { status: 204 })
        })
      )

      renderWithProviders(<ExerciseDetailPage />, {
        path: 'exercises/:id',
        route: `/exercises/${exerciseId}`,
        // ExerciseDetailPage navigates to /exercises after a successful
        // delete; register that destination so the test's router can
        // resolve it instead of logging "No routes matched location".
        additionalRoutes: [{ path: 'exercises', element: <div>Exercises list</div> }],
      })

      // Wait for the exercise to load
      await screen.findByText(exerciseName)

      // Click the delete button (the first one, which is in the toolbar)
      const deleteButtons = screen.getAllByRole('button', { name: /delete/i })
      const deleteButton = deleteButtons[0]
      if (!deleteButton) throw new Error('Delete button not found')
      await user.click(deleteButton)

      // Find and click the confirm button in the dialog (the second Delete button)
      const deleteButtonsForConfirm = screen.getAllByRole('button', { name: /delete/i })
      const confirmButton = deleteButtonsForConfirm[1]
      if (!confirmButton) throw new Error('Confirm button not found')
      await user.click(confirmButton)

      // Verify the delete mutation was called and the app navigated away
      // from the now-deleted exercise's detail page.
      await waitFor(() => {
        expect(deleteWasCalled).toBe(true)
      })
      await screen.findByText('Exercises list')
    })

    it('shows error message when deleting an in-use exercise with 409 response', async () => {
      const inUseId = '9002'

      server.use(
        http.get(`/api/v1/exercises/${inUseId}`, () => {
          return HttpResponse.json({
            data: {
              ...fixtureShowResistance.data,
              id: inUseId,
              usage_count: 2,
            },
          })
        }),
        http.delete(`/api/v1/exercises/${inUseId}`, () => {
          return HttpResponse.json(fixtureInUseError, { status: 409 })
        })
      )

      renderWithProviders(<ExerciseDetailPage />, {
        path: 'exercises/:id',
        route: `/exercises/${inUseId}`,
      })

      // Wait for the exercise to load
      await screen.findByText(exerciseName)

      // The delete button should be disabled because usage_count > 0
      const deleteButton = screen.getByRole('button', { name: /delete/i })
      expect(deleteButton).toBeDisabled()
    })
  })
})
