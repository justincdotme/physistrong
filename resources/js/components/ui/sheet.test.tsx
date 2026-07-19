import { describe, it, expect, vi } from 'vitest'
import { render, screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { Sheet } from './sheet'

describe('Sheet', () => {
  const defaultProps = {
    open: true,
    onClose: vi.fn(),
    title: 'Test Sheet',
    children: <p>Sheet content</p>,
  }

  it('renders a dialog with the title as accessible name', () => {
    render(<Sheet {...defaultProps} />)

    const dialog = screen.getByRole('dialog', { name: 'Test Sheet' })
    expect(dialog).toBeInTheDocument()
    expect(dialog).toHaveAttribute('aria-modal', 'true')
  })

  it('renders nothing when closed', () => {
    render(<Sheet {...defaultProps} open={false} />)

    expect(screen.queryByRole('dialog')).not.toBeInTheDocument()
  })

  it('dismisses on Escape', async () => {
    const onClose = vi.fn()
    const user = userEvent.setup()

    render(<Sheet {...defaultProps} onClose={onClose} />)

    await user.keyboard('{Escape}')
    expect(onClose).toHaveBeenCalledTimes(1)
  })

  it('renders footer when provided', () => {
    render(<Sheet {...defaultProps} footer={<button>Save</button>} />)

    expect(screen.getByRole('button', { name: 'Save' })).toBeInTheDocument()
  })

  it('renders children content', () => {
    render(<Sheet {...defaultProps} />)

    expect(screen.getByText('Sheet content')).toBeInTheDocument()
  })
})
