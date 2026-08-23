// Civil dates (workout date, date-only "YYYY-MM-DD" from the API) are calendar days.
// Format them via string parsing only. Never construct a Date from a date-only string:
// new Date('2026-07-03') parses as UTC midnight and toLocaleDateString renders in
// local time, producing off-by-one errors west of UTC.
// Instants (created_at timestamps with time+zone) use Date objects and are UTC on wire.
// todayISO() uses new Date() with no args to get current local time. That is correct.

const MONTHS = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']

export function formatDate(iso: string): string {
  if (!iso) return ''
  const parts = iso.split('-').map(Number)
  const month = parts[1] ?? 1
  return `${MONTHS[month - 1]} ${parts[2]}, ${parts[0]}`
}

export function formatDateCompact(iso: string): string {
  if (!iso) return ''
  const parts = iso.split('-')
  return `${parts[1]}/${parts[2]}`
}

export function todayISO(): string {
  const n = new Date()
  return `${n.getFullYear()}-${String(n.getMonth() + 1).padStart(2, '0')}-${String(n.getDate()).padStart(2, '0')}`
}
