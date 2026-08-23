import { Fragment, useRef, useState } from 'react'
import type { ChangeEvent, CSSProperties, FocusEvent, KeyboardEvent } from 'react'
import { cn } from '@/lib/utils'
import { fromSeconds, toSeconds } from '@/lib/duration'

export interface DurationInputProps {
  value: number | null
  onChange: (seconds: number | null) => void
  label: string
  id?: string
  disabled?: boolean
  className?: string
  style?: CSSProperties
}

const UNITS = ['hours', 'minutes', 'seconds'] as const

type Segments = [string, string, string]

function segmentsFor(value: number | null): Segments {
  if (value == null) return ['', '', '']
  const { hours, minutes, seconds } = fromSeconds(value)
  return [hours, minutes, seconds].map(n => String(n).padStart(2, '0')) as Segments
}

/**
 * Three inputs styled as one field, so the browser owns focus on tap and there
 * is no caret arithmetic. Digits arrive through onChange rather than onKeyDown,
 * which keeps the field working on Android keyboards that report no usable key
 * while composing.
 */
export function DurationInput({
  value,
  onChange,
  label,
  id,
  disabled,
  className,
  style,
}: DurationInputProps) {
  // Null while idle so the field follows an external value change. Typing
  // seeds it, letting a segment hold a raw entry like "90" until blur.
  const [draft, setDraft] = useState<Segments | null>(null)
  const refs = useRef<Array<HTMLInputElement | null>>([null, null, null])
  const shown = draft ?? segmentsFor(value)

  const setSegment = (index: number, raw: string) => {
    const digits = raw.replace(/\D/g, '').slice(0, 2)
    const next = [...shown] as Segments
    next[index] = digits
    setDraft(next)
    if (digits.length === 2 && index < UNITS.length - 1) refs.current[index + 1]?.focus()
  }

  // Normalization runs when focus leaves the whole field rather than each
  // segment, so auto-advancing between segments cannot reflow the value out
  // from under a half-finished entry.
  const handleBlur = (e: FocusEvent<HTMLDivElement>) => {
    if (e.currentTarget.contains(e.relatedTarget)) return
    setDraft(null)
    if (shown.every(segment => segment === '')) {
      if (value != null) onChange(null)
      return
    }
    const total = toSeconds(Number(shown[0] || 0), Number(shown[1] || 0), Number(shown[2] || 0))
    if (total !== value) onChange(total)
  }

  const handleKeyDown = (index: number) => (e: KeyboardEvent<HTMLInputElement>) => {
    if (e.key === 'Backspace' && e.currentTarget.value === '' && index > 0) {
      e.preventDefault()
      refs.current[index - 1]?.focus()
    }
  }

  return (
    <div
      role="group"
      onBlur={handleBlur}
      className={cn(
        'inline-flex items-center justify-center tabular-nums',
        disabled && 'opacity-60',
        className
      )}
      style={style}
    >
      {UNITS.map((unit, index) => (
        <Fragment key={unit}>
          {index > 0 && <span aria-hidden="true">:</span>}
          <input
            ref={el => {
              refs.current[index] = el
            }}
            id={index === 0 ? id : undefined}
            type="text"
            inputMode="numeric"
            pattern="[0-9]*"
            autoComplete="off"
            disabled={disabled}
            aria-label={`${label} ${unit}`}
            placeholder="00"
            value={shown[index]}
            onChange={(e: ChangeEvent<HTMLInputElement>) => setSegment(index, e.target.value)}
            onFocus={e => e.target.select()}
            onKeyDown={handleKeyDown(index)}
            className="w-[2ch] bg-transparent text-center outline-none"
          />
        </Fragment>
      ))}
    </div>
  )
}
