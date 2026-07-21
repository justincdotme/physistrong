import { useEffect, useRef, useState } from 'react'
import { Pencil } from 'lucide-react'
import { cn } from '@/lib/utils'

export interface InlineEditProps {
  value: string
  onChange: (value: string) => void
  placeholder?: string
  className?: string
  inputClassName?: string
  ariaLabel?: string
  duskDataAttribute?: string
}

export function InlineEdit({
  value,
  onChange,
  placeholder,
  className,
  inputClassName,
  ariaLabel,
  duskDataAttribute,
}: InlineEditProps) {
  const [editing, setEditing] = useState(false)
  const [draft, setDraft] = useState(value)
  const ref = useRef<HTMLInputElement>(null)

  useEffect(() => {
    if (editing && ref.current) {
      ref.current.focus()
      ref.current.select()
    }
  }, [editing])

  const commit = () => {
    setEditing(false)
    if (draft.trim() && draft !== value) {
      onChange(draft.trim())
    } else {
      setDraft(value)
    }
  }

  if (editing) {
    return (
      <input
        ref={ref}
        value={draft}
        placeholder={placeholder}
        aria-label={ariaLabel}
        onChange={e => setDraft(e.target.value)}
        onBlur={commit}
        onKeyDown={e => {
          if (e.key === 'Enter') commit()
          if (e.key === 'Escape') {
            setDraft(value)
            setEditing(false)
          }
        }}
        className={cn('ps-input px-2 py-1 bg-transparent', inputClassName)}
      />
    )
  }

  return (
    <button
      onClick={() => {
        setDraft(value)
        setEditing(true)
      }}
      className={cn('text-left inline-flex items-center gap-1.5 group', className)}
      aria-label={ariaLabel}
      {...(duskDataAttribute && { dusk: duskDataAttribute })}
    >
      <span>{value || placeholder}</span>
      <Pencil
        size={15}
        className="opacity-0 group-hover:opacity-60 text-text-muted transition-opacity"
      />
    </button>
  )
}
