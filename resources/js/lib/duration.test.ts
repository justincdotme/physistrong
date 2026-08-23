import { describe, it, expect } from 'vitest'
import { formatDuration, fromSeconds, toSeconds } from './duration'

describe('formatDuration', () => {
  it.each([
    [null, ''],
    [undefined, ''],
  ])('returns empty string for %s', (input, expected) => {
    expect(formatDuration(input as number | null | undefined)).toBe(expected)
  })

  it.each([
    [0, '00:00'],
    [9, '00:09'],
    [45, '00:45'],
    [59, '00:59'],
    [60, '01:00'],
    [90, '01:30'],
    [540, '09:00'],
    [2700, '45:00'],
    [3599, '59:59'],
    [3600, '01:00:00'],
    [3661, '01:01:01'],
    [5400, '01:30:00'],
    [36000, '10:00:00'],
  ])('pads every segment and adds hours only at an hour: %d -> %s', (input, expected) => {
    expect(formatDuration(input)).toBe(expected)
  })
})

describe('toSeconds and fromSeconds', () => {
  // The overflow rollover the input relies on: raw segments in, canonical
  // seconds out, then back to segments that always fit the field.
  it.each([
    [[0, 0, 90], 90, '00:01:30'],
    [[0, 99, 99], 6039, '01:40:39'],
    [[0, 0, 9], 9, '00:00:09'],
    [[0, 9, 0], 540, '00:09:00'],
    [[0, 45, 0], 2700, '00:45:00'],
    [[0, 90, 0], 5400, '01:30:00'],
    [[1, 30, 0], 5400, '01:30:00'],
    [[0, 0, 0], 0, '00:00:00'],
  ])('normalizes %j to %d seconds, redisplayed as %s', (segments, expected, redisplayed) => {
    const [hours, minutes, seconds] = segments as [number, number, number]
    const total = toSeconds(hours, minutes, seconds)
    expect(total).toBe(expected)

    const back = fromSeconds(total)
    const padded = [back.hours, back.minutes, back.seconds]
      .map(n => String(n).padStart(2, '0'))
      .join(':')
    expect(padded).toBe(redisplayed)
  })

  it('clamps hours at 99 rather than rolling into a fourth segment', () => {
    expect(toSeconds(99, 99, 99)).toBe(359999)
    expect(fromSeconds(toSeconds(99, 99, 99))).toEqual({ hours: 99, minutes: 59, seconds: 59 })
  })
})
