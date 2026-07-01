import { describe, it, expect, vi, afterEach } from 'vitest'
import { formatDate, formatDateShort, formatDuration, todayISO } from './formatters'

describe('formatDuration', () => {
  it.each([
    [null, ''],
    [undefined, ''],
  ])('returns empty string for null/undefined: %s', (input, expected) => {
    expect(formatDuration(input as number | null | undefined)).toBe(expected)
  })

  it.each([
    [0, '0s'],
    [1, '1s'],
    [45, '45s'],
    [59, '59s'],
  ])('formats seconds under 60: %d → %s', (input, expected) => {
    expect(formatDuration(input)).toBe(expected)
  })

  it.each([
    [60, '1:00'],
    [61, '1:01'],
    [120, '2:00'],
    [125, '2:05'],
    [599, '9:59'],
    [600, '10:00'],
    [3661, '61:01'],
  ])('formats minutes with zero-padded seconds: %d → %s', (input, expected) => {
    expect(formatDuration(input)).toBe(expected)
  })
})

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

describe('formatDateShort', () => {
  it.each([['', '']])('returns empty string for empty input: "%s"', (input, expected) => {
    expect(formatDateShort(input)).toBe(expected)
  })

  it.each([
    ['2026-06-30', 'Jun 30'],
    ['2026-01-01', 'Jan 1'],
    ['2026-12-31', 'Dec 31'],
    ['2025-02-14', 'Feb 14'],
    ['2000-03-15', 'Mar 15'],
    ['1999-11-09', 'Nov 9'],
  ])('formats ISO date to "Mon DD": %s → %s', (input, expected) => {
    expect(formatDateShort(input)).toBe(expected)
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
      const result = formatDateShort(`2026-${String(index + 1).padStart(2, '0')}-15`)
      expect(result).toBe(`${month} 15`)
    })
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
