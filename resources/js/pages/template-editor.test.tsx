import { describe, it, expect, beforeEach } from 'vitest'
import { http, HttpResponse } from 'msw'
import { QueryClient, QueryClientProvider, useQuery } from '@tanstack/react-query'
import { MemoryRouter, Routes, Route } from 'react-router-dom'
import { vi } from 'vitest'
import { render } from '@testing-library/react'
import { renderWithProviders, screen, userEvent, waitFor, testUser } from '@/test/render'
import { server } from '@/test/server'
import { AppProvider } from '@/lib/store'
import { AuthContext } from '@/lib/auth-context'
import { templateQueries } from '@/api/templates'
import { TemplateEditorPage } from './template-editor'
import type { RawWorkoutTemplate } from '@/api/transformers'

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

      const templateName = await screen.findByText('Capture Test Template 1782864881129')
      expect(templateName).toBeInTheDocument()

      const exercise1 = await screen.findByText('Barbell Back Squat')
      expect(exercise1).toBeInTheDocument()

      const exercise2 = await screen.findByText('Bench Press')
      expect(exercise2).toBeInTheDocument()

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

      server.use(
        http.get('/api/v1/templates/:id', () => HttpResponse.json({ data: currentTemplate })),
        http.delete('/api/v1/templates/:templateId/exercises/:exerciseId', ({ params }) => {
          deletedExerciseId = params.exerciseId as string
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

      const exercise1 = await screen.findByText('Barbell Back Squat')
      expect(exercise1).toBeInTheDocument()

      const removeButtons = screen.getAllByLabelText('Remove from template')
      expect(removeButtons.length).toBeGreaterThan(0)

      const removeButton = removeButtons[0]
      if (!removeButton) throw new Error('Remove button not found')
      await user.click(removeButton)

      await waitFor(() => {
        expect(deletedExerciseId).toBe('101')
      })

      await waitFor(() => {
        expect(screen.queryByText('Barbell Back Squat')).not.toBeInTheDocument()
      })

      expect(screen.getByText('Bench Press')).toBeInTheDocument()
    })
  })

  describe('list cache invalidation', () => {
    it(
      'invalidates the templates list after detaching an exercise',
      { timeout: 15000 },
      async () => {
        let listFetchCount = 0
        let currentTemplate = { ...rawTemplateWithExercises }

        server.use(
          http.get('/api/v1/templates', () => {
            listFetchCount++
            return HttpResponse.json({ data: [] })
          }),
          http.get('/api/v1/templates/:id', () => HttpResponse.json({ data: currentTemplate })),
          http.delete('/api/v1/templates/:templateId/exercises/:exerciseId', ({ params }) => {
            currentTemplate = {
              ...currentTemplate,
              exercises: currentTemplate.exercises.filter(e => e.id !== Number(params.exerciseId)),
            }
            return new HttpResponse(null, { status: 204 })
          })
        )

        renderEditorWithListObserver('2')

        await screen.findByText('Barbell Back Squat')
        const fetchesBeforeDetach = listFetchCount

        const removeButtons = screen.getAllByLabelText('Remove from template')
        const removeButton = removeButtons[0]
        if (!removeButton) throw new Error('Remove button not found')
        await userEvent.click(removeButton)

        await waitFor(() => {
          expect(listFetchCount).toBeGreaterThan(fetchesBeforeDetach)
        })
      }
    )
  })
})

function TemplateListObserver() {
  useQuery(templateQueries.list())
  return null
}

function renderEditorWithListObserver(templateId: string) {
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
      <MemoryRouter initialEntries={[`/templates/${templateId}`]}>
        <AuthContext.Provider value={auth}>
          <AppProvider>
            <TemplateListObserver />
            <Routes>
              <Route path="templates/:id" element={<TemplateEditorPage />} />
            </Routes>
          </AppProvider>
        </AuthContext.Provider>
      </MemoryRouter>
    </QueryClientProvider>
  )
}
