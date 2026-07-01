import { describe, it, expect } from 'vitest'
import { http, HttpResponse } from 'msw'
import { renderWithProviders, screen, userEvent, waitFor } from '@/test/render'
import { server } from '@/test/server'
import { ExerciseProgressPage } from './exercise-progress'
import progressResistance from '@/test/mocks/fixtures/progress/progress-resistance.json'

describe('ExerciseProgressPage', () => {
  describe('Record cards render real data', () => {
    it('displays record card with weight value and unit from fixture', async () => {
      renderWithProviders(<ExerciseProgressPage />, {
        path: 'exercises/:id/progress',
        route: '/exercises/895/progress',
      })

      // Wait for the page to fully load (exercise name appears)
      await screen.findByText('Capture Test Resistance 1782864880612')

      // Find the "Heaviest" label (weight record)
      const heaviestLabel = await screen.findByText('Heaviest')

      // Verify the weight value "105" appears in the same record card
      const recordCard = heaviestLabel.closest('.ps-card')
      expect(recordCard).toBeInTheDocument()
      expect(recordCard).toHaveTextContent('105')

      // Verify the unit label "lb" appears in this card
      expect(recordCard).toHaveTextContent('lb')
    })

    it('displays multiple personal records from the fixture', async () => {
      renderWithProviders(<ExerciseProgressPage />, {
        path: 'exercises/:id/progress',
        route: '/exercises/895/progress',
      })

      // Wait for the page to load
      await screen.findByText('Capture Test Resistance 1782864880612')

      // Wait for records to load by checking for a specific record value
      await screen.findByText('105')

      // Verify all three record types are displayed by checking for their values
      expect(screen.getByText('105')).toBeInTheDocument() // weight: 105 (Heaviest)
      expect(screen.getByText('10')).toBeInTheDocument() // reps: 10 (Most reps)
      expect(screen.getByText('1000')).toBeInTheDocument() // volume: 1000 (Top set volume)
    })
  })

  describe('TimeRange control re-queries with new range', () => {
    it('sends correct range query param when TimeRange control is changed', async () => {
      const user = userEvent.setup()
      let capturedRangeParam: string | null = null

      // Intercept the progress endpoint to capture the range query param
      server.use(
        http.get('/api/v1/exercises/:id/progress', ({ request }) => {
          const url = new URL(request.url)
          capturedRangeParam = url.searchParams.get('range')
          return HttpResponse.json(progressResistance)
        })
      )

      renderWithProviders(<ExerciseProgressPage />, {
        path: 'exercises/:id/progress',
        route: '/exercises/895/progress',
      })

      // Wait for page to load and records to appear
      await screen.findByText('Capture Test Resistance 1782864880612')
      await screen.findByText('105')

      // Find and click the "3M" button in the TimeRange control (using tab role)
      const threeMonthButton = screen.getByRole('tab', { name: '3M' })
      await user.click(threeMonthButton)

      // Wait for the new query to be captured and verify the range param
      await waitFor(() => {
        expect(capturedRangeParam).toBe('3m')
      })
    })

    it('sends correct range param when clicking different TimeRange options', async () => {
      const user = userEvent.setup()
      const capturedParams: string[] = []

      // Intercept to capture all range params
      server.use(
        http.get('/api/v1/exercises/:id/progress', ({ request }) => {
          const url = new URL(request.url)
          const range = url.searchParams.get('range')
          if (range) {
            capturedParams.push(range)
          }
          return HttpResponse.json(progressResistance)
        })
      )

      renderWithProviders(<ExerciseProgressPage />, {
        path: 'exercises/:id/progress',
        route: '/exercises/895/progress',
      })

      // Wait for initial load (default is '6M', so should be '6m' in lowercase)
      await screen.findByText('Capture Test Resistance 1782864880612')
      await screen.findByText('105')

      // Initial request should have captured the default range
      expect(capturedParams).toContain('6m')

      // Click "1Y" option (using tab role)
      const oneYearButton = screen.getByRole('tab', { name: '1Y' })
      await user.click(oneYearButton)

      // Verify the new range param was captured
      await waitFor(() => {
        expect(capturedParams).toContain('1y')
      })

      // Click "All" option (using tab role)
      const allButton = screen.getByRole('tab', { name: 'All' })
      await user.click(allButton)

      // Verify the new range param
      await waitFor(() => {
        expect(capturedParams).toContain('all')
      })
    })
  })
})
