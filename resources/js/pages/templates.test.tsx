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

      await screen.findByText('Capture Test Template')

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

      await screen.findByText('Capture Test Template')

      const createButton = screen.getByRole('button', { name: 'Create' })
      await user.click(createButton)

      const nameInput = screen.getByLabelText('Template name')
      await user.type(nameInput, 'Push Day')

      // The modal Sheet hides outside content from the a11y tree, so only
      // the Sheet footer's Create button is visible to getByRole.
      const submitButton = screen.getByRole('button', { name: 'Create' })
      await user.click(submitButton)

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

      await screen.findByText('Test Template')

      const deleteButton = screen.getByLabelText('Delete Test Template')
      await user.click(deleteButton)

      await screen.findByText(/Test Template.*will be removed from your templates/)

      const allDeleteButtons = screen.queryAllByRole('button', { name: 'Delete' })
      expect(allDeleteButtons.length).toBeGreaterThan(0)
      const confirmButton = allDeleteButtons[allDeleteButtons.length - 1]
      expect(confirmButton).toBeDefined()
      if (confirmButton) {
        await user.click(confirmButton)
      }

      await waitFor(
        () => {
          expect(deleteWasCalled).toBe(true)
        },
        { timeout: 5000 }
      )
    })
  })
})
