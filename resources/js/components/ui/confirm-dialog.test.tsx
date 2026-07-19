import { describe, it, expect, vi } from 'vitest'
import { render, screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { ConfirmDialog } from './confirm-dialog'

describe('ConfirmDialog', () => {
  const defaultProps = {
    open: true,
    title: 'Delete item?',
    message: 'This action cannot be undone.',
    onConfirm: vi.fn(),
    onCancel: vi.fn(),
  }

  it('renders a dialog with the title as accessible name', () => {
    render(<ConfirmDialog {...defaultProps} />)

    const dialog = screen.getByRole('dialog', { name: 'Delete item?' })
    expect(dialog).toBeInTheDocument()
    expect(dialog).toHaveAttribute('aria-modal', 'true')
  })

  it('wires the message as accessible description', () => {
    render(<ConfirmDialog {...defaultProps} />)

    const dialog = screen.getByRole('dialog', { name: 'Delete item?' })
    expect(dialog).toHaveAccessibleDescription('This action cannot be undone.')
  })

  it('renders nothing when closed', () => {
    render(<ConfirmDialog {...defaultProps} open={false} />)

    expect(screen.queryByRole('dialog')).not.toBeInTheDocument()
  })

  it('dismisses on Escape', async () => {
    const onCancel = vi.fn()
    const user = userEvent.setup()

    render(<ConfirmDialog {...defaultProps} onCancel={onCancel} />)

    await user.keyboard('{Escape}')
    expect(onCancel).toHaveBeenCalledTimes(1)
  })

  it('uses the custom confirmLabel', () => {
    render(<ConfirmDialog {...defaultProps} confirmLabel="Remove" />)

    expect(screen.getByRole('button', { name: 'Remove' })).toBeInTheDocument()
  })

  it('defaults confirmLabel to Delete', () => {
    render(<ConfirmDialog {...defaultProps} />)

    expect(screen.getByRole('button', { name: 'Delete' })).toBeInTheDocument()
  })
})
