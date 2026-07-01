import { describe, it, expect } from 'vitest'
import { http, HttpResponse } from 'msw'
import { renderWithProviders, screen, userEvent, waitFor } from '@/test/render'
import { server } from '@/test/server'
import { TemplatesPage } from './templates'
import fixtureList from '@/test/mocks/fixtures/templates/list.json'
import type { WorkoutTemplateListItem } from '@/api/types'

describe('TemplatesPage', () => {
  describe('List renders from fixture', () => {
    it('displays templates from the fixture with correct count in subtitle', async () => {
      renderWithProviders(<TemplatesPage />)

      // Wait for a template from the fixture to appear
      await screen.findByText('Capture Test Template 1782864881129')

      // Assert subtitle shows the count
      const templateCount = fixtureList.data.length
      expect(screen.getByText(`${templateCount} saved`)).toBeInTheDocument()
    })
  })

  describe('Create flow', () => {
    it('posts the correct payload when creating a template', { timeout: 15000 }, async () => {
      const user = userEvent.setup()
      let capturedPayload: Record<string, unknown> | null = null

      server.use(
        http.post('/api/v1/templates', async ({ request }) => {
          capturedPayload = (await request.json()) as Record<string, unknown>
          return HttpResponse.json(
            {
              data: {
                id: 999,
                name: 'Push Day',
                notes: null,
                exercises: [],
                created_at: '2026-06-30T00:00:00.000000Z',
                updated_at: '2026-06-30T00:00:00.000000Z',
              },
            },
            { status: 201 }
          )
        })
      )

      renderWithProviders(<TemplatesPage />)

      // Wait for templates to load
      await screen.findByText('Capture Test Template 1782864881129')

      // Click the Create button in PageHeader
      const createButton = screen.getByRole('button', { name: 'Create' })
      await user.click(createButton)

      // Type template name in the input
      const nameInput = screen.getByLabelText('Template name')
      await user.type(nameInput, 'Push Day')

      // Click the Create button in Sheet footer - find by getting all "Create" buttons and using the last one
      const allCreateButtons = screen.queryAllByRole('button', { name: 'Create' })
      expect(allCreateButtons.length).toBeGreaterThan(1)
      const submitButton = allCreateButtons[allCreateButtons.length - 1]
      expect(submitButton).toBeDefined()
      if (submitButton) {
        await user.click(submitButton)
      }

      // Wait for mutation to complete and verify payload
      await waitFor(
        () => {
          expect(capturedPayload).toEqual({
            name: 'Push Day',
          })
        },
        { timeout: 5000 }
      )
    })
  })

  describe('Delete flow', () => {
    it('deletes a template when confirmed', { timeout: 15000 }, async () => {
      const user = userEvent.setup()
      let deleteWasCalled = false

      // Override list to include a user-owned template
      const userTemplate: WorkoutTemplateListItem = {
        id: '999',
        name: 'Test Template',
        notes: null,
        exercises: [],
      }

      server.use(
        http.get('/api/v1/templates', () =>
          HttpResponse.json({
            data: [...fixtureList.data, userTemplate],
          })
        ),
        http.delete('/api/v1/templates/:id', ({ params }) => {
          if (params.id === '999') {
            deleteWasCalled = true
          }
          return new HttpResponse(null, { status: 204 })
        })
      )

      renderWithProviders(<TemplatesPage />)

      // Wait for the user template to appear
      await screen.findByText('Test Template')

      // Click the delete button for the user template
      const deleteButton = screen.getByLabelText('Delete Test Template')
      await user.click(deleteButton)

      // Wait for the confirmation dialog to appear
      await screen.findByText(/Test Template.*will be removed from your templates/)

      // Find and click the Delete button in the confirmation dialog
      const allDeleteButtons = screen.queryAllByRole('button', { name: 'Delete' })
      expect(allDeleteButtons.length).toBeGreaterThan(0)
      const confirmButton = allDeleteButtons[allDeleteButtons.length - 1]
      expect(confirmButton).toBeDefined()
      if (confirmButton) {
        await user.click(confirmButton)
      }

      // Wait for the delete API to be called
      await waitFor(
        () => {
          expect(deleteWasCalled).toBe(true)
        },
        { timeout: 5000 }
      )
    })
  })
})
