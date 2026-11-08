import { describe, it, expect, vi, afterEach } from 'vitest'
import { formatDate, formatDateCompact, todayISO } from './formatters'

describe('formatDate', () => {
  it.each([['', '']])('returns empty string for empty input: "%s"', (input, expected) => {
    expect(formatDate(input)).toBe(expected)
  })

  it.each([
    ['2026-06-30', 'Jun 30, 2026'],
    ['2026-01-01', 'Jan 1, 2026'],
    ['2026-12-31', 'Dec 31, 2026'],
    ['2025-02-14', 'Feb 14, 2025'],
    ['2000-03-15', 'Mar 15, 2000'],
    ['1999-11-09', 'Nov 9, 1999'],
  ])('formats ISO date to "Mon DD, YYYY": %s → %s', (input, expected) => {
    expect(formatDate(input)).toBe(expected)
  })

  it('handles all 12 months', () => {
    const months = [
      'Jan',
      'Feb',
      'Mar',
      'Apr',
      'May',
      'Jun',
      'Jul',
      'Aug',
      'Sep',
      'Oct',
      'Nov',
      'Dec',
    ]
    months.forEach((month, index) => {
      const result = formatDate(`2026-${String(index + 1).padStart(2, '0')}-15`)
      expect(result).toBe(`${month} 15, 2026`)
    })
  })
})

describe('formatDateCompact', () => {
  it.each([['', '']])('returns empty string for empty input: "%s"', (input, expected) => {
    expect(formatDateCompact(input)).toBe(expected)
  })

  it.each([
    ['2026-06-30', '06/30'],
    ['2026-01-01', '01/01'],
    ['2026-12-31', '12/31'],
    ['2025-02-14', '02/14'],
    ['2000-03-05', '03/05'],
    ['1999-11-09', '11/09'],
  ])('formats ISO date to "MM/DD": %s → %s', (input, expected) => {
    expect(formatDateCompact(input)).toBe(expected)
  })
})

describe('todayISO', () => {
  it('returns ISO format date string for today', () => {
    const result = todayISO()
    expect(result).toMatch(/^\d{4}-\d{2}-\d{2}$/)
  })

  it('returns date matching today', () => {
    const today = new Date()
    const result = todayISO()
    const year = today.getFullYear()
    const month = String(today.getMonth() + 1).padStart(2, '0')
    const day = String(today.getDate()).padStart(2, '0')
    expect(result).toBe(`${year}-${month}-${day}`)
  })

  afterEach(() => {
    vi.useRealTimers()
  })
})

describe('timezone safety - civil date formatters never shift dates', () => {
  it.each([
    ['2026-01-01', 'Jan 1, 2026', '01/01'],
    ['2026-07-03', 'Jul 3, 2026', '07/03'],
    ['2026-12-31', 'Dec 31, 2026', '12/31'],
  ])(
    'preserves calendar day %s regardless of runtime timezone',
    (iso, expectedFull, expectedCompact) => {
      expect(formatDate(iso)).toBe(expectedFull)
      expect(formatDateCompact(iso)).toBe(expectedCompact)
    }
  )
})
