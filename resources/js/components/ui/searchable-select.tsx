import { useState, useRef, useEffect } from 'react'
import * as Popover from '@radix-ui/react-popover'
import { ChevronDown, Search } from 'lucide-react'
import { cn } from '@/lib/utils'

export interface SearchableSelectOption {
  value: string
  label: string
  suffix?: string
}

interface SearchableSelectProps {
  options: SearchableSelectOption[]
  value: string
  onChange: (value: string) => void
  placeholder?: string
  className?: string
}

export function SearchableSelect({
  options,
  value,
  onChange,
  placeholder = 'Select...',
  className,
}: SearchableSelectProps) {
  const [open, setOpen] = useState(false)
  const [search, setSearch] = useState('')
  const inputRef = useRef<HTMLInputElement>(null)

  const filtered = search
    ? options.filter(o => o.label.toLowerCase().includes(search.toLowerCase()))
    : options
  const selected = options.find(o => o.value === value)

  useEffect(() => {
    if (open) {
      setSearch('')
      requestAnimationFrame(() => inputRef.current?.focus())
    }
  }, [open])

  return (
    <Popover.Root open={open} onOpenChange={setOpen}>
      <Popover.Trigger asChild>
        <button
          type="button"
          className={cn(
            'ps-input w-full pl-3 pr-10 py-3 text-sm font-semibold text-left relative cursor-pointer',
            className
          )}
          dusk="exercise-picker-trigger"
        >
          <span className="block truncate">{selected?.label ?? placeholder}</span>
          <ChevronDown
            size={18}
            className="absolute right-3 top-1/2 -translate-y-1/2 text-text-muted"
          />
        </button>
      </Popover.Trigger>
      <Popover.Portal>
        <Popover.Content
          className="bg-surface-card rounded-lg shadow-xl border border-border z-50 w-[var(--radix-popover-trigger-width)]"
          sideOffset={4}
          align="start"
          onOpenAutoFocus={e => e.preventDefault()}
        >
          <div className="p-2 border-b border-border">
            <div className="relative">
              <Search
                size={16}
                className="absolute left-2.5 top-1/2 -translate-y-1/2 text-text-muted"
              />
              <input
                ref={inputRef}
                type="text"
                value={search}
                onChange={e => setSearch(e.target.value)}
                placeholder="Search exercises..."
                className="ps-input w-full pl-8 pr-3 py-2 text-sm"
                dusk="exercise-search-input"
              />
            </div>
          </div>
          <div className="max-h-60 overflow-y-auto p-1">
            {filtered.length === 0 ? (
              <div className="py-3 px-2 text-sm text-text-muted text-center">
                No exercises found
              </div>
            ) : (
              filtered.map(o => (
                <button
                  key={o.value}
                  type="button"
                  onClick={() => {
                    onChange(o.value)
                    setOpen(false)
                  }}
                  className={cn(
                    'w-full text-left px-3 py-2 rounded-md text-sm transition-colors',
                    o.value === value
                      ? 'bg-primary/10 text-primary font-semibold'
                      : 'hover:bg-surface-muted'
                  )}
                >
                  {o.label}
                  {o.suffix && (
                    <span className="text-text-muted ml-1.5 font-normal">({o.suffix})</span>
                  )}
                </button>
              ))
            )}
          </div>
        </Popover.Content>
      </Popover.Portal>
    </Popover.Root>
  )
}
