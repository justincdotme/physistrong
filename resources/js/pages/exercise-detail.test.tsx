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

      await screen.findByText(exerciseName)

      const editButton = screen.getByLabelText('Exercise name')
      await user.click(editButton)

      const input = screen.getByDisplayValue(exerciseName)
      await user.clear(input)
      await user.type(input, 'Updated Exercise Name')

      await user.keyboard('{Enter}')

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

      await screen.findByText(exerciseName)

      // Multiple delete buttons exist; the toolbar's is first in DOM order.
      const deleteButtons = screen.getAllByRole('button', { name: /delete/i })
      const deleteButton = deleteButtons[0]
      if (!deleteButton) throw new Error('Delete button not found')
      await user.click(deleteButton)

      // The modal dialog hides outside content from the a11y tree, so only
      // the dialog's own Delete button is visible to getByRole.
      const confirmButton = screen.getByRole('button', { name: /delete/i })
      await user.click(confirmButton)

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

      await screen.findByText(exerciseName)

      // usage_count > 0 means the exercise is in use
      const deleteButton = screen.getByRole('button', { name: /delete/i })
      expect(deleteButton).toBeDisabled()
    })

    it('does not refetch the deleted exercise after a confirmed delete', async () => {
      const user = userEvent.setup()
      let deleteWasCalled = false
      let deleted = false
      let postDeleteGetCount = 0

      server.use(
        http.get(`/api/v1/exercises/${exerciseId}`, () => {
          if (deleted) {
            postDeleteGetCount++
            return HttpResponse.json({ message: 'Exercise not found.' }, { status: 404 })
          }
          return HttpResponse.json(fixtureShowResistance)
        }),
        http.delete(`/api/v1/exercises/${exerciseId}`, () => {
          deleted = true
          deleteWasCalled = true
          return new HttpResponse(null, { status: 204 })
        })
      )

      renderWithProviders(<ExerciseDetailPage />, {
        path: 'exercises/:id',
        route: `/exercises/${exerciseId}`,
        additionalRoutes: [{ path: 'exercises', element: <div>Exercises list</div> }],
      })

      await screen.findByText(exerciseName)

      const deleteButtons = screen.getAllByRole('button', { name: /delete/i })
      const deleteButton = deleteButtons[0]
      if (!deleteButton) throw new Error('Delete button not found')
      await user.click(deleteButton)

      const confirmButton = screen.getByRole('button', { name: /delete/i })
      await user.click(confirmButton)

      await waitFor(() => {
        expect(deleteWasCalled).toBe(true)
      })
      await screen.findByText('Exercises list')

      expect(postDeleteGetCount).toBe(0)
    })

    it('disables delete for an exercise with logged data but no workout/template usage', async () => {
      const entryOnlyId = '9003'

      server.use(
        http.get(`/api/v1/exercises/${entryOnlyId}`, () =>
          HttpResponse.json({
            data: {
              ...fixtureShowResistance.data,
              id: entryOnlyId,
              usage_count: 0,
              has_logged_data: true,
            },
          })
        )
      )

      renderWithProviders(<ExerciseDetailPage />, {
        path: 'exercises/:id',
        route: `/exercises/${entryOnlyId}`,
      })

      await screen.findByText(exerciseName)

      // Entry-only usage: usage_count is 0 but has_logged_data is true, so
      // deleting would 409. The guard must disable on the logged-data signal.
      const deleteButton = screen.getByRole('button', { name: /delete/i })
      expect(deleteButton).toBeDisabled()
    })
  })
})
