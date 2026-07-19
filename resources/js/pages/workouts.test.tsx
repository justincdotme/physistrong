import { describe, it, expect } from 'vitest'
import { screen, renderWithProviders, userEvent } from '@/test/render'
import { WorkoutsPage } from './workouts'

describe('WorkoutsPage', () => {
  it('renders total count and first page workouts from paginated fixture', async () => {
    renderWithProviders(<WorkoutsPage />, { path: '/workouts', route: '/workouts' })

    const totalText = await screen.findByText(/21 logged/)
    expect(totalText).toBeInTheDocument()

    const workoutName = await screen.findByText('Test with Entries 18')
    expect(workoutName).toBeInTheDocument()
  })

  it('loads and renders page 2 when Load More button is clicked', async () => {
    renderWithProviders(<WorkoutsPage />, { path: '/workouts', route: '/workouts' })

    await screen.findByText(/21 logged/)

    const loadMoreBtn = await screen.findByRole('button', { name: /load more/i })
    expect(loadMoreBtn).toBeInTheDocument()

    await userEvent.click(loadMoreBtn)

    const page2Workout = await screen.findByText('Test with Entries 4')
    expect(page2Workout).toBeInTheDocument()
  })
})
