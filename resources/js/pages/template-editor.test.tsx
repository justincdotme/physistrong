import { describe, it, expect, beforeEach } from 'vitest'
import { http, HttpResponse } from 'msw'
import { renderWithProviders, screen, userEvent, waitFor } from '@/test/render'
import { server } from '@/test/server'
import { TemplateEditorPage } from './template-editor'
import type { RawWorkoutTemplate } from '@/api/transformers'

// Raw template response with exercises for testing
const rawTemplateWithExercises: RawWorkoutTemplate = {
  id: 2,
  name: 'Capture Test Template 1782864881129',
  notes: null,
  exercises: [
    {
      id: 101,
      name: 'Barbell Back Squat',
      type: 'resistance',
      equipment_type_id: 1,
      exercise_order: 1,
      template_entry_group_id: null,
    },
    {
      id: 102,
      name: 'Bench Press',
      type: 'resistance',
      equipment_type_id: 12,
      exercise_order: 2,
      template_entry_group_id: null,
    },
  ],
  groups: [],
  created_at: '2026-01-01T00:00:00.000000Z',
  updated_at: '2026-01-01T00:00:00.000000Z',
}

describe('TemplateEditorPage', () => {
  beforeEach(() => {
    // Override the template GET handler to return our fixture with exercises
    server.use(
      http.get('/api/v1/templates/:id', () => HttpResponse.json({ data: rawTemplateWithExercises }))
    )
  })

  describe('Load and render template', () => {
    it('renders the template name and attached exercises from fixture', async () => {
      renderWithProviders(<TemplateEditorPage />, {
        path: 'templates/:id',
        route: '/templates/2',
      })

      // Wait for template name to render in the inline edit
      const templateName = await screen.findByText('Capture Test Template 1782864881129')
      expect(templateName).toBeInTheDocument()

      // Verify first exercise renders
      const exercise1 = await screen.findByText('Barbell Back Squat')
      expect(exercise1).toBeInTheDocument()

      // Verify second exercise renders
      const exercise2 = await screen.findByText('Bench Press')
      expect(exercise2).toBeInTheDocument()

      // Verify equipment names render
      const barbeDisc = await screen.findByText('barbell')
      expect(barbeDisc).toBeInTheDocument()

      const bench = await screen.findByText('bench')
      expect(bench).toBeInTheDocument()
    })
  })

  describe('Detach exercise mutation', () => {
    it('removes exercise from template when delete button clicked', async () => {
      const user = userEvent.setup()
      let deletedExerciseId: string | null = null
      let currentTemplate = { ...rawTemplateWithExercises }

      // Override handlers to track deletion and update the template state
      server.use(
        http.get('/api/v1/templates/:id', () => HttpResponse.json({ data: currentTemplate })),
        http.delete('/api/v1/templates/:templateId/exercises/:exerciseId', ({ params }) => {
          deletedExerciseId = params.exerciseId as string
          // Update the current template by removing the deleted exercise
          currentTemplate = {
            ...currentTemplate,
            exercises: currentTemplate.exercises.filter(e => e.id !== Number(params.exerciseId)),
          }
          return new HttpResponse(null, { status: 204 })
        })
      )

      renderWithProviders(<TemplateEditorPage />, {
        path: 'templates/:id',
        route: '/templates/2',
      })

      // Wait for exercises to render
      const exercise1 = await screen.findByText('Barbell Back Squat')
      expect(exercise1).toBeInTheDocument()

      // Find and click the remove button for the first exercise
      // The remove button is near the exercise name
      const removeButtons = screen.getAllByLabelText('Remove from template')
      expect(removeButtons.length).toBeGreaterThan(0)

      // Click the first remove button
      const removeButton = removeButtons[0]
      if (!removeButton) throw new Error('Remove button not found')
      await user.click(removeButton)

      // Verify the delete request was made with correct exercise ID
      await waitFor(() => {
        // The page passes exerciseId from the TemplateExercise.exerciseId which maps to raw.id
        expect(deletedExerciseId).toBe('101')
      })

      // Verify the first exercise is removed from the DOM
      await waitFor(() => {
        expect(screen.queryByText('Barbell Back Squat')).not.toBeInTheDocument()
      })

      // Verify the second exercise still exists
      expect(screen.getByText('Bench Press')).toBeInTheDocument()
    })
  })
})
