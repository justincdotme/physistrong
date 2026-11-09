import { describe, it, expect, vi, beforeAll } from 'vitest'
import { render, screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { SearchableSelect } from './searchable-select'

const options = [
  { value: '1', label: 'Bench Press' },
  { value: '2', label: 'Back Squat' },
]

beforeAll(() => {
  vi.stubGlobal(
    'ResizeObserver',
    class {
      observe() {}
      unobserve() {}
      disconnect() {}
    }
  )
})

describe('SearchableSelect', () => {
  it('shows every option again when reopened after a filtered search', async () => {
    const user = userEvent.setup()
    render(<SearchableSelect options={options} value="" onChange={vi.fn()} />)

    const trigger = screen.getByRole('button', { name: 'Select...' })
    await user.click(trigger)
    await user.type(await screen.findByPlaceholderText('Search...'), 'bench')
    expect(screen.queryByRole('button', { name: 'Back Squat' })).not.toBeInTheDocument()

    await user.keyboard('{Escape}')
    await user.click(screen.getByRole('button', { name: 'Select...' }))

    expect(await screen.findByPlaceholderText('Search...')).toHaveValue('')
    expect(screen.getByRole('button', { name: 'Back Squat' })).toBeInTheDocument()
  })
})
