import { describe, it, expect } from 'vitest'
import { http, HttpResponse } from 'msw'
import { renderWithProviders, screen, userEvent, waitFor } from '@/test/render'
import { server } from '@/test/server'
import { ExerciseProgressPage } from './exercise-progress'
import progressResistance from '@/test/mocks/fixtures/progress/progress-resistance.json'
import progressDistance from '@/test/mocks/fixtures/progress/progress-distance.json'
import exerciseDistance from '@/test/mocks/fixtures/exercises/show-distance.json'

describe('ExerciseProgressPage', () => {
  describe('Record cards render real data', () => {
    it('displays record card with weight value and unit from fixture', async () => {
      renderWithProviders(<ExerciseProgressPage />, {
        path: 'exercises/:id/progress',
        route: '/exercises/895/progress',
      })

      await screen.findByText('Capture Test Resistance')

      const heaviestLabel = await screen.findByText('Heaviest')

      const recordCard = heaviestLabel.closest('.ps-card')
      expect(recordCard).toBeInTheDocument()
      expect(recordCard).toHaveTextContent('180')

      expect(recordCard).toHaveTextContent('lb')
    })

    it('displays multiple personal records from the fixture', async () => {
      renderWithProviders(<ExerciseProgressPage />, {
        path: 'exercises/:id/progress',
        route: '/exercises/895/progress',
      })

      await screen.findByText('Capture Test Resistance')

      await screen.findByText('180')

      expect(screen.getByText('180')).toBeInTheDocument() // weight: 180 (Heaviest)
      expect(screen.getByText('10')).toBeInTheDocument() // reps: 10 (Most reps)
      expect(screen.getByText('1040')).toBeInTheDocument() // volume: 1040 (Top set volume)
    })
  })

  describe('TimeRange control re-queries with new range', () => {
    it('sends correct range query param when TimeRange control is changed', async () => {
      const user = userEvent.setup()
      let capturedRangeParam: string | null = null

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

      await screen.findByText('Capture Test Resistance')
      await screen.findByText('180')

      const threeMonthButton = screen.getByRole('tab', { name: '3M' })
      await user.click(threeMonthButton)

      await waitFor(() => {
        expect(capturedRangeParam).toBe('3m')
      })
    })

    it('sends correct range param when clicking different TimeRange options', async () => {
      const user = userEvent.setup()
      const capturedParams: string[] = []

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

      // The 6M default is lowercased to 6m on the wire.
      await screen.findByText('Capture Test Resistance')
      await screen.findByText('180')

      expect(capturedParams).toContain('6m')

      const oneYearButton = screen.getByRole('tab', { name: '1Y' })
      await user.click(oneYearButton)

      await waitFor(() => {
        expect(capturedParams).toContain('1y')
      })

      const allButton = screen.getByRole('tab', { name: 'All' })
      await user.click(allButton)

      await waitFor(() => {
        expect(capturedParams).toContain('all')
      })
    })
  })
})

describe('Distance exercises chart time as well as distance', () => {
  const distanceRoute = `/exercises/${exerciseDistance.data.id}/progress`

  it('offers both metrics and charts the duration series when selected', async () => {
    const user = userEvent.setup()

    renderWithProviders(<ExerciseProgressPage />, {
      path: 'exercises/:id/progress',
      route: distanceRoute,
    })

    await screen.findByText(exerciseDistance.data.name)

    expect(await screen.findByRole('tab', { name: 'Distance' })).toBeInTheDocument()
    const durationTab = screen.getByRole('tab', { name: 'Duration' })

    await user.click(durationTab)

    await waitFor(() => expect(durationTab).toHaveAttribute('aria-selected', 'true'))
    expect(screen.getByText(/Duration over time/i)).toBeInTheDocument()
  })

  it('says nothing was logged for a metric with no all-time data', async () => {
    const user = userEvent.setup()
    const durationSeries = progressDistance.data.metrics.filter(m => m.metric === 'duration')

    server.use(
      http.get('/api/v1/exercises/:id/progress', () =>
        HttpResponse.json({
          data: {
            ...progressDistance.data,
            metrics: [{ metric: 'distance', has_data: false, data_points: [] }, ...durationSeries],
          },
        })
      )
    )

    renderWithProviders(<ExerciseProgressPage />, {
      path: 'exercises/:id/progress',
      route: distanceRoute,
    })

    await screen.findByText(exerciseDistance.data.name)
    await user.click(await screen.findByRole('tab', { name: 'Distance' }))

    expect(await screen.findByText(/No distance logged for this exercise yet/i)).toBeInTheDocument()
    expect(screen.queryByText(/No logged sets for this exercise yet/i)).not.toBeInTheDocument()
  })
})
