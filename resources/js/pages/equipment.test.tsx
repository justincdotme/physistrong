import { describe, it, expect } from 'vitest'
import { http, HttpResponse } from 'msw'
import { renderWithProviders, screen, userEvent, waitFor } from '@/test/render'
import { server } from '@/test/server'
import { EquipmentPage } from './equipment'
import fixtureList from '@/test/mocks/fixtures/equipment/list.json'

describe('EquipmentPage', () => {
  describe('List renders from fixture', () => {
    it('displays equipment from the fixture', async () => {
      renderWithProviders(<EquipmentPage />)

      // Wait for an equipment item from the fixture to appear
      const barbeElement = await screen.findByText('barbell')
      expect(barbeElement).toBeInTheDocument()

      // Assert subtitle shows the count
      const equipmentCount = fixtureList.data.length
      expect(screen.getByText(`${equipmentCount} types`)).toBeInTheDocument()
    })
  })

  describe('Create flow', () => {
    it('posts the correct payload when creating equipment', async () => {
      const user = userEvent.setup()
      let capturedPayload: unknown = null

      server.use(
        http.post('/api/v1/equipment-types', async ({ request }) => {
          capturedPayload = await request.json()
          return HttpResponse.json(
            {
              data: {
                id: 999,
                name: 'Sled',
                is_system: false,
                user_id: 1,
                created_at: '2026-06-30T00:00:00.000000Z',
                updated_at: '2026-06-30T00:00:00.000000Z',
                usage_count: 0,
              },
            },
            { status: 201 }
          )
        })
      )

      renderWithProviders(<EquipmentPage />)

      // Wait for the list to load
      await screen.findByText('barbell')

      // Click the Add button
      const addButton = screen.getByRole('button', { name: /^Add$/ })
      await user.click(addButton)

      // Type in the equipment name input
      const nameInput = screen.getByLabelText(/^Name$/)
      await user.type(nameInput, 'Sled')

      // Click Add Equipment button
      const addEquipmentButton = screen.getByRole('button', { name: /^Add Equipment$/ })
      await user.click(addEquipmentButton)

      // Wait for the mutation to complete and verify the payload
      await waitFor(() => {
        expect(capturedPayload).toEqual({
          name: 'Sled',
        })
      })
    })
  })

  describe('Delete flow including 409 in-use path', () => {
    it('handles 409 error when deleting equipment in use', async () => {
      const user = userEvent.setup()
      let capturedDeleteId: string | null = null

      // Override list to include a custom equipment item and intercept delete with 409 response
      server.use(
        http.get('/api/v1/equipment-types', () =>
          HttpResponse.json({
            data: [
              ...fixtureList.data,
              {
                id: 9002,
                name: 'Test Deletable Equipment',
                is_system: false,
                user_id: 3,
                created_at: '2026-06-30T00:00:00.000000Z',
                updated_at: '2026-06-30T00:00:00.000000Z',
                usage_count: 0,
              },
            ],
          })
        ),
        http.delete('/api/v1/equipment-types/:id', ({ params }) => {
          capturedDeleteId = String(params.id)
          if (params.id === '9002') {
            return HttpResponse.json(
              {
                message: 'Equipment type is in use by exercises.',
              },
              { status: 409 }
            )
          }
          return new HttpResponse(null, { status: 204 })
        })
      )

      renderWithProviders(<EquipmentPage />)

      // Wait for the test equipment to appear
      await screen.findByText('Test Deletable Equipment')

      // Click the delete button for the test equipment
      const deleteButton = screen.getByLabelText(/^Delete Test Deletable Equipment$/)
      await user.click(deleteButton)

      // Confirm the deletion in the dialog
      const deleteButtons = screen.getAllByRole('button', { name: 'Delete' })
      const confirmButton = deleteButtons.find(btn => !btn.hasAttribute('disabled'))
      if (!confirmButton) {
        throw new Error('Confirm button not found')
      }
      await user.click(confirmButton)

      // Verify the confirm dialog closes (mutation was attempted)
      await waitFor(() => {
        expect(screen.queryByText('Delete equipment?')).not.toBeInTheDocument()
      })

      // Verify the delete request was made with the correct ID
      await waitFor(() => {
        expect(capturedDeleteId).toBe('9002')
      })
    })
  })
})
