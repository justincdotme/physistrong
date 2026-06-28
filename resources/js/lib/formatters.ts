const MONTHS = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']

export function formatDate(iso: string): string {
  if (!iso) return ''
  const parts = iso.split('-').map(Number)
  const month = parts[1] ?? 1
  return `${MONTHS[month - 1]} ${parts[2]}, ${parts[0]}`
}

export function formatDateShort(iso: string): string {
  if (!iso) return ''
  const parts = iso.split('-').map(Number)
  const month = parts[1] ?? 1
  return `${MONTHS[month - 1]} ${parts[2]}`
}

export function todayISO(): string {
  const n = new Date()
  return `${n.getFullYear()}-${String(n.getMonth() + 1).padStart(2, '0')}-${String(n.getDate()).padStart(2, '0')}`
}

export function formatDuration(s: number | null | undefined): string {
  if (s == null) return ''
  if (s < 60) return `${s}s`
  const m = Math.floor(s / 60)
  const r = s % 60
  return `${m}:${String(r).padStart(2, '0')}`
}
