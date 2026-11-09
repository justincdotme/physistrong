import { useState, type ReactNode } from 'react'
import { describe, it, expect, vi } from 'vitest'
import { screen, renderWithProviders, userEvent } from '@/test/render'
import { GroupConfigSheet, NewWorkoutWizard } from './pickers'

function Reopenable({ children }: { children: (open: boolean, close: () => void) => ReactNode }) {
  const [open, setOpen] = useState(true)
  return (
    <>
      <button onClick={() => setOpen(true)}>Reopen</button>
      {children(open, () => setOpen(false))}
    </>
  )
}

async function closeAndReopen() {
  await userEvent.click(screen.getByRole('button', { name: 'Close' }))
  await userEvent.click(await screen.findByRole('button', { name: 'Reopen' }))
}

describe('sheets reopened after being closed', () => {
  it('returns the new workout wizard to its first step', async () => {
    renderWithProviders(
      <Reopenable>
        {(open, close) => <NewWorkoutWizard open={open} onClose={close} onCreateEmpty={vi.fn()} />}
      </Reopenable>,
      { path: '/', route: '/' }
    )

    await userEvent.click(screen.getByRole('button', { name: /start from scratch/i }))
    expect(screen.getByLabelText('Workout name')).toBeInTheDocument()

    await closeAndReopen()

    expect(await screen.findByRole('button', { name: /start from scratch/i })).toBeInTheDocument()
    expect(screen.queryByLabelText('Workout name')).not.toBeInTheDocument()
  })

  it('clears the group name entered in the previous session', async () => {
    renderWithProviders(
      <Reopenable>
        {(open, close) => (
          <GroupConfigSheet open={open} onClose={close} count={2} onConfirm={vi.fn()} />
        )}
      </Reopenable>,
      { path: '/', route: '/' }
    )

    await userEvent.type(screen.getByPlaceholderText('e.g. Chest Superset'), 'Leg Superset')

    await closeAndReopen()

    expect(await screen.findByPlaceholderText('e.g. Chest Superset')).toHaveValue('')
  })
})
