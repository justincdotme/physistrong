import { describe, it, expect } from 'vitest'
import { screen, renderWithProviders, userEvent } from '@/test/render'
import { WorkoutsPage } from './workouts'

describe('WorkoutsPage', () => {
  it('renders total count and first page workouts from paginated fixture', async () => {
    renderWithProviders(<WorkoutsPage />, { path: '/workouts', route: '/workouts' })

    // Wait for the total to render in the subtitle
    const totalText = await screen.findByText(/21 logged/)
    expect(totalText).toBeInTheDocument()

    // Verify a workout name from page 1 appears
    const workoutName = await screen.findByText('Test with Entries 18')
    expect(workoutName).toBeInTheDocument()
  })

  it('loads and renders page 2 when Load More button is clicked', async () => {
    renderWithProviders(<WorkoutsPage />, { path: '/workouts', route: '/workouts' })

    // Wait for initial page to load
    await screen.findByText(/21 logged/)

    // Find and click the Load More button
    const loadMoreBtn = await screen.findByRole('button', { name: /load more/i })
    expect(loadMoreBtn).toBeInTheDocument()

    await userEvent.click(loadMoreBtn)

    // Wait for a workout name unique to page 2 to appear
    const page2Workout = await screen.findByText('Test with Entries 4')
    expect(page2Workout).toBeInTheDocument()
  })
})
