import { useState } from 'react'
import { describe, it, expect, vi } from 'vitest'
import { render, screen, fireEvent } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { DurationInput } from './duration-input'

// The value round-trips through state, so the display assertions reflect what
// a real parent would render back after a commit.
function Harness({
  initial,
  onChange,
}: {
  initial: number | null
  onChange: (seconds: number | null) => void
}) {
  const [value, setValue] = useState(initial)
  return (
    <DurationInput
      value={value}
      label="Hold"
      onChange={seconds => {
        setValue(seconds)
        onChange(seconds)
      }}
    />
  )
}

function setup(value: number | null = null) {
  const onChange = vi.fn()
  render(<Harness initial={value} onChange={onChange} />)
  return {
    onChange,
    hours: screen.getByLabelText('Hold hours') as HTMLInputElement,
    minutes: screen.getByLabelText('Hold minutes') as HTMLInputElement,
    seconds: screen.getByLabelText('Hold seconds') as HTMLInputElement,
  }
}

describe('DurationInput display', () => {
  it.each([
    [45, ['00', '00', '45']],
    [9, ['00', '00', '09']],
    [540, ['00', '09', '00']],
    [5400, ['01', '30', '00']],
  ])('renders %d seconds across the three segments as %j', (value, expected) => {
    const { hours, minutes, seconds } = setup(value)
    expect([hours.value, minutes.value, seconds.value]).toEqual(expected)
  })

  it('leaves every segment empty when there is no value', () => {
    const { hours, minutes, seconds } = setup(null)
    expect([hours.value, minutes.value, seconds.value]).toEqual(['', '', ''])
  })

  it('shows the numeric keypad on mobile for every segment', () => {
    const { hours, minutes, seconds } = setup(null)
    for (const segment of [hours, minutes, seconds]) {
      expect(segment).toHaveAttribute('inputMode', 'numeric')
      expect(segment).toHaveAttribute('pattern', '[0-9]*')
    }
  })
})

describe('DurationInput entry', () => {
  it('commits 9 seconds when 9 is typed into the seconds segment', async () => {
    const user = await userEvent.setup()
    const { onChange, seconds } = setup(null)

    await user.click(seconds)
    await user.keyboard('9')
    await user.tab()

    expect(onChange).toHaveBeenCalledWith(9)
  })

  it('commits 9 minutes when 9 is typed into the minutes segment', async () => {
    const user = userEvent.setup()
    const { onChange, minutes } = setup(null)

    await user.click(minutes)
    await user.keyboard('9')
    await user.tab()
    await user.tab()

    expect(onChange).toHaveBeenCalledWith(540)
  })

  it('rolls 90 seconds up to one minute thirty on blur', async () => {
    const user = userEvent.setup()
    const { onChange, seconds, minutes, hours } = setup(null)

    await user.click(seconds)
    await user.keyboard('90')
    await user.tab()

    expect(onChange).toHaveBeenCalledWith(90)
    expect([hours.value, minutes.value, seconds.value]).toEqual(['00', '01', '30'])
  })

  it('auto-advances to the next segment once a segment holds two digits', async () => {
    const user = userEvent.setup()
    const { onChange, minutes, seconds } = setup(null)

    await user.click(minutes)
    await user.keyboard('4500')

    expect(document.activeElement).toBe(seconds)

    await user.tab()
    expect(onChange).toHaveBeenCalledWith(2700)
  })

  it('moves focus back a segment when backspace hits an empty one', async () => {
    const user = userEvent.setup()
    const { minutes, seconds } = setup(null)

    await user.click(seconds)
    await user.keyboard('{Backspace}')

    expect(document.activeElement).toBe(minutes)
  })

  it('treats an empty segment as zero alongside a filled one', async () => {
    const user = userEvent.setup()
    const { onChange, hours } = setup(null)

    await user.click(hours)
    await user.keyboard('02')
    await user.tab()
    await user.tab()

    expect(onChange).toHaveBeenCalledWith(7200)
  })

  it('clears to null when every segment is emptied', async () => {
    const user = userEvent.setup()
    const { onChange, hours, minutes, seconds } = setup(90)

    for (const segment of [hours, minutes, seconds]) {
      await user.click(segment)
      await user.keyboard('{Backspace}')
    }
    await user.tab()

    expect(onChange).toHaveBeenCalledWith(null)
  })

  it('keeps only the first two digits of a pasted segment', async () => {
    const user = userEvent.setup()
    const { onChange, seconds } = setup(null)

    await user.click(seconds)
    await user.paste('1245')
    await user.tab()

    expect(onChange).toHaveBeenCalledWith(12)
  })

  // Guards the reason digits are handled in onChange rather than onKeyDown.
  // Android keyboards commit text without reporting a usable key, and this is
  // the only case here that fails if key interception comes back.
  it('accepts a digit that arrives with no keydown', () => {
    const { seconds } = setup(null)

    seconds.focus()
    fireEvent.input(seconds, { target: { value: '7' } })

    expect(seconds.value).toBe('7')
  })

  it('does not report a change when the field is focused and left alone', async () => {
    const user = userEvent.setup()
    const { onChange, minutes } = setup(90)

    await user.click(minutes)
    await user.tab()
    await user.tab()

    expect(onChange).not.toHaveBeenCalled()
  })
})
