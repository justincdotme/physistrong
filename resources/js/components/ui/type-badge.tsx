import { Badge } from './badge'
import type { ExerciseType } from '@/api/types'
import { EXERCISE_TYPES } from '@/lib/exercise-types'

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
  return <Badge tone={TYPE_TONE[type] || 'neutral'}>{EXERCISE_TYPES[type].label}</Badge>
}
