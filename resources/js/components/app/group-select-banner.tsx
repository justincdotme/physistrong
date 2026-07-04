import { Check } from 'lucide-react'
import { Button } from '@/components/ui'

interface GroupSelectBannerProps {
  selectedCount: number
  canContinue: boolean
  onContinue: () => void
  onCancel: () => void
}

export function GroupSelectBanner({
  selectedCount,
  canContinue,
  onContinue,
  onCancel,
}: GroupSelectBannerProps) {
  return (
    <div
      className="ps-card p-4 mb-4"
      style={{
        border: '1px solid var(--color-primary)',
        background: 'color-mix(in srgb, var(--color-primary) 6%, var(--color-surface-card))',
      }}
    >
      <div className="flex items-start gap-3">
        <span
          className="h-9 w-9 rounded-lg flex items-center justify-center shrink-0"
          style={{
            background: 'color-mix(in srgb, var(--color-primary) 14%, transparent)',
            color: 'var(--color-primary)',
          }}
        >
          <Check size={18} />
        </span>
        <div className="flex-1 min-w-0">
          <div className="font-semibold text-sm">Build a superset or circuit</div>
          <div className="text-[13px] text-text-secondary mt-0.5">
            {
              "Tap 2 or more exercises below to combine them. You'll set rounds and rest in the next step."
            }
          </div>
        </div>
      </div>
      <div className="flex items-center gap-2 mt-3">
        <Button size="sm" disabled={!canContinue} onClick={onContinue}>
          Continue · {selectedCount} selected
        </Button>
        <Button size="sm" variant="ghost" onClick={onCancel}>
          Cancel
        </Button>
      </div>
    </div>
  )
}
