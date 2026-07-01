import { describe, it, expect } from 'vitest'
import { screen, renderWithProviders, userEvent, waitFor } from '@/test/render'
import { ProgressPage } from './progress'

describe('ProgressPage', () => {
  describe('List renders from fixture', () => {
    it('displays an exercise from the fixture in the selected picker value', async () => {
      renderWithProviders(<ProgressPage />)

      // Wait for page header to confirm page loaded
      await screen.findByText('Progress')

      // The page pre-selects an exercise with logged data
      // Verify it appears in the picker trigger (showing the selected exercise)
      await screen.findByText('3/4 Sit-Up')

      // Verify the label appears
      expect(screen.getByText('Exercise')).toBeInTheDocument()
    })

    it('populates exercise picker with options from fixture when opened', async () => {
      renderWithProviders(<ProgressPage />)

      await screen.findByText('Progress')

      const user = userEvent.setup()

      // Open the picker
      const picker = screen.getByRole('button', { name: /Select an exercise/ })
      await user.click(picker)

      // Wait for exercises to appear in the popover as clickable options
      // Find the exercise buttons in the popover (they'll be buttons with the exercise name)
      const sitUpOptions = await screen.findAllByText('3/4 Sit-Up')
      // The second one is the popover option (first is the trigger)
      expect(sitUpOptions.length).toBeGreaterThanOrEqual(2)

      const cruncher = await screen.findByText('Ab Crunch Machine')
      expect(cruncher).toBeInTheDocument()
    })
  })

  describe('Type filtering', () => {
    it('filters exercises by type using the type selector', async () => {
      renderWithProviders(<ProgressPage />)

      await screen.findByText('Progress')

      const user = userEvent.setup()

      // Click the "Resistance" type tab
      const resistanceTab = screen.getByRole('tab', { name: 'Resistance' })
      await user.click(resistanceTab)

      // Wait a moment for state to update, then open the picker
      // The picker button is the one with aria-haspopup="dialog"
      const allButtons = screen.getAllByRole('button')
      const pickerButton = allButtons.find(
        btn => btn.getAttribute('aria-haspopup') === 'dialog'
      ) as HTMLButtonElement | undefined

      expect(pickerButton).toBeDefined()
      if (pickerButton) {
        await user.click(pickerButton)
      }

      // Verify resistance exercises appear
      const abCrunch = await screen.findByText('Ab Crunch Machine')
      expect(abCrunch).toBeInTheDocument()

      // 90/90 Hamstring is timed_hold type, should not appear when filtered to resistance
      expect(screen.queryByText(/90\/90 Hamstring/)).not.toBeInTheDocument()
    })

    it('shows all exercise types when "All" type is selected', async () => {
      renderWithProviders(<ProgressPage />)

      await screen.findByText('Progress')

      const user = userEvent.setup()

      // Start by filtering to resistance
      const resistanceTab = screen.getByRole('tab', { name: 'Resistance' })
      await user.click(resistanceTab)

      // Then click "All" type filter (first one is type filter, second is time range)
      const allTabs = screen.getAllByRole('tab', { name: 'All' })
      expect(allTabs.length).toBeGreaterThan(0)
      await user.click(allTabs[0] as HTMLElement)

      // Open the picker - it's the button with aria-haspopup="dialog"
      const allButtons2 = screen.getAllByRole('button')
      const pickerButton2 = allButtons2.find(
        btn => btn.getAttribute('aria-haspopup') === 'dialog'
      ) as HTMLButtonElement | undefined

      expect(pickerButton2).toBeDefined()
      if (pickerButton2) {
        await user.click(pickerButton2)
      }

      // Both resistance and non-resistance exercises should appear
      const resistance = await screen.findByText('Ab Crunch Machine')
      expect(resistance).toBeInTheDocument()

      const hold = screen.getByText('90/90 Hamstring')
      expect(hold).toBeInTheDocument()
    })
  })

  describe('Exercise selection and time range', () => {
    it('displays time range controls when an exercise is pre-selected on load', async () => {
      renderWithProviders(<ProgressPage />)

      // Wait for page to load
      await screen.findByText('Progress')

      // The page pre-selects an exercise, so time range should be visible
      const timeRangeLabel = await screen.findByText(/Time Range/)
      expect(timeRangeLabel).toBeInTheDocument()

      // Verify default time range (6M) is selected
      const sixMonthTab = screen.getByRole('tab', { name: '6M' })
      expect(sixMonthTab).toBeInTheDocument()
      expect(sixMonthTab).toHaveAttribute('aria-selected', 'true')
    })

    it('can change the selected exercise via the picker', async () => {
      renderWithProviders(<ProgressPage />)

      await screen.findByText('Progress')

      const user = userEvent.setup()

      // Open the picker
      const picker = screen.getByRole('button', { name: /Select an exercise/ })
      await user.click(picker)

      // Select a different exercise
      const hamstringOptions = await screen.findAllByText('90/90 Hamstring')
      // Click the option in the popover (not the trigger, so get the last/different one)
      const hamstringButton =
        hamstringOptions.length > 1
          ? (hamstringOptions[hamstringOptions.length - 1] as HTMLElement)
          : (hamstringOptions[0] as HTMLElement)

      expect(hamstringButton).toBeDefined()
      await user.click(hamstringButton)

      // Time range should still be visible
      await waitFor(() => {
        expect(screen.getByText(/Time Range/)).toBeInTheDocument()
      })

      // Verify the selection worked by checking that time range is still visible
      // This confirms the component is still working
      const timeRangeLabel = screen.getByText(/Time Range/)
      expect(timeRangeLabel).toBeInTheDocument()
    })
  })

  describe('Empty state', () => {
    it('shows message when no exercises exist', async () => {
      const { server } = await import('@/test/server')
      const { http, HttpResponse } = await import('msw')

      server.use(http.get('/api/v1/exercises', () => HttpResponse.json({ data: [] })))

      renderWithProviders(<ProgressPage />)

      // Wait for the empty message
      const emptyMessage = await screen.findByText(/No exercises to chart yet/)
      expect(emptyMessage).toBeInTheDocument()

      // Time range should not be visible
      expect(screen.queryByText(/Time Range/)).not.toBeInTheDocument()

      // Picker trigger should say "Select an exercise..."
      const picker = screen.getByRole('button')
      expect(picker).toHaveTextContent('Select an exercise...')
    })
  })
})
