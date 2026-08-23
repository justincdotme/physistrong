import { describe, it, expect, vi } from 'vitest'
import { screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { renderWithProviders } from '@/test/render'
import { EntryMetrics } from './metric-inputs'
import type { WorkoutEntry, Exercise } from '@/api/types'

const plank = {
  id: 1,
  name: 'Plank',
  type: 'timed_hold',
  targetDurationSeconds: null,
} as unknown as Exercise

const entry = {
  id: 10,
  exerciseId: 1,
  durationMetric: { targetDurationSeconds: null, actualDurationSeconds: 45 },
} as unknown as WorkoutEntry

describe('EntryMetrics duration fields', () => {
  it('spreads a logged hold across the three segments', async () => {
    renderWithProviders(
      <EntryMetrics entry={entry} exercise={plank} allTimeBest={null} onChange={vi.fn()} />
    )

    expect(await screen.findByLabelText('Hold hours')).toHaveValue('00')
    expect(screen.getByLabelText('Hold minutes')).toHaveValue('00')
    expect(screen.getByLabelText('Hold seconds')).toHaveValue('45')
  })

  it('reports minutes typed into the hold field back as seconds', async () => {
    const onChange = vi.fn()
    const user = userEvent.setup()
    renderWithProviders(
      <EntryMetrics entry={entry} exercise={plank} allTimeBest={null} onChange={onChange} />
    )

    const minutes = await screen.findByLabelText('Hold minutes')
    await user.click(minutes)
    await user.keyboard('02')
    await user.tab()
    await user.tab()

    expect(onChange).toHaveBeenLastCalledWith({
      durationMetric: { targetDurationSeconds: null, actualDurationSeconds: 165 },
    })
  })
})
