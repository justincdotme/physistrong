// Durations are integer seconds everywhere except the screen. Two-digit
// segments, hours only once the value reaches one, so a 9-second plank reads
// 00:09 rather than 00:00:09.

// 99:59:59. Hours clamp rather than roll over, so a duration can never render
// wider than the three segments the input shows.
export const MAX_DURATION_SECONDS = 359999

export function formatDuration(s: number | null | undefined): string {
  if (s == null) return ''
  const total = Math.max(0, Math.round(s))
  const minutes = String(Math.floor((total % 3600) / 60)).padStart(2, '0')
  const seconds = String(total % 60).padStart(2, '0')
  const hours = Math.floor(total / 3600)
  if (hours === 0) return `${minutes}:${seconds}`
  return `${String(hours).padStart(2, '0')}:${minutes}:${seconds}`
}

export function toSeconds(hours: number, minutes: number, seconds: number): number {
  return Math.min(hours * 3600 + minutes * 60 + seconds, MAX_DURATION_SECONDS)
}

export function fromSeconds(total: number): { hours: number; minutes: number; seconds: number } {
  const clamped = Math.min(Math.max(0, Math.round(total)), MAX_DURATION_SECONDS)
  return {
    hours: Math.floor(clamped / 3600),
    minutes: Math.floor((clamped % 3600) / 60),
    seconds: clamped % 60,
  }
}
