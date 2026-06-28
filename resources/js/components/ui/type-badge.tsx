import { Badge } from './badge'
import type { ExerciseType } from '@/api/types'

const TYPE_LABELS: Record<ExerciseType, string> = {
  resistance: 'Resistance',
  timed_hold: 'Timed Hold',
  distance: 'Distance',
  interval: 'Interval',
}

const TYPE_TONE: Record<ExerciseType, 'primary' | 'secondary' | 'accent' | 'neutral'> = {
  resistance: 'primary',
  timed_hold: 'secondary',
  distance: 'secondary',
  interval: 'accent',
}

export interface TypeBadgeProps {
  type: ExerciseType
}

export function TypeBadge({ type }: TypeBadgeProps) {
  return <Badge tone={TYPE_TONE[type] || 'neutral'}>{TYPE_LABELS[type]}</Badge>
}
