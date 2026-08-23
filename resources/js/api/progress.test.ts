import { describe, it, expect } from 'vitest'
import { transformProgressData, extractAllTimeBest } from './progress'
import type { RawProgressResponse, RawRecordsResponse } from './progress'
import progressDistance from '@/test/mocks/fixtures/progress/progress-distance.json'
import recordsDistance from '@/test/mocks/fixtures/progress/records-distance.json'

describe('transformProgressData', () => {
  it('maps data points with string entry IDs and isPR flag', () => {
    const progress: RawProgressResponse = {
      exercise_id: 1,
      exercise_type: 'resistance',
      range: '6m',
      primary_metric: 'weight',
      metrics: [
        {
          metric: 'weight',
          has_data: true,
          data_points: [
            { entry_id: 10, date: '2026-06-01', value: 185.0, is_pr: false },
            { entry_id: 20, date: '2026-06-15', value: 205.0, is_pr: true },
          ],
        },
      ],
    }

    const records: RawRecordsResponse = {
      exercise_id: 1,
      exercise_type: 'resistance',
      records: {},
    }

    const result = transformProgressData(progress, records, 'imperial')

    expect(result.series[0]?.points).toHaveLength(2)
    expect(result.series[0]?.points[0]).toEqual({
      date: '2026-06-01',
      value: 185.0,
      reps: null,
      entryId: '10',
      isPR: false,
    })
    expect(result.series[0]?.points[1]).toEqual({
      date: '2026-06-15',
      value: 205.0,
      reps: null,
      entryId: '20',
      isPR: true,
    })
  })

  it('maps volume data', () => {
    const progress: RawProgressResponse = {
      exercise_id: 1,
      exercise_type: 'resistance',
      range: '6m',
      primary_metric: 'weight',
      metrics: [{ metric: 'weight', has_data: false, data_points: [] }],
      volume: [
        { workout_id: 1, date: '2026-06-01', total_volume: 3700.0 },
        { workout_id: 2, date: '2026-06-02', total_volume: 4200.5 },
      ],
    }

    const records: RawRecordsResponse = {
      exercise_id: 1,
      exercise_type: 'resistance',
      records: {},
    }

    const result = transformProgressData(progress, records, 'imperial')

    expect(result.volume).toHaveLength(2)
    expect(result.volume[0]).toEqual({ date: '2026-06-01', value: 3700.0 })
    expect(result.volume[1]).toEqual({ date: '2026-06-02', value: 4200.5 })
  })

  it('maps records with correct labels and imperial units for weight', () => {
    const progress: RawProgressResponse = {
      exercise_id: 1,
      exercise_type: 'resistance',
      range: '6m',
      primary_metric: 'weight',
      metrics: [{ metric: 'weight', has_data: false, data_points: [] }],
    }

    const records: RawRecordsResponse = {
      exercise_id: 1,
      exercise_type: 'resistance',
      records: {
        weight: { value: 225.0, entry_id: 5, date: '2026-05-20' },
        reps: { value: 12, entry_id: 8, date: '2026-06-10' },
        volume: { value: 2700.0, entry_id: 9, date: '2026-06-10' },
      },
    }

    const result = transformProgressData(progress, records, 'imperial')

    expect(result.records).toHaveLength(3)
    expect(result.records[0]).toEqual({
      label: 'Heaviest',
      value: '225',
      unit: 'lb',
      sub: null,
    })
    expect(result.records[1]).toEqual({
      label: 'Most reps',
      value: '12',
      unit: '',
      sub: null,
    })
    expect(result.records[2]).toEqual({
      label: 'Top set volume',
      value: '2700',
      unit: 'lb',
      sub: null,
    })
  })

  it('maps records with metric units for weight and distance', () => {
    const progress: RawProgressResponse = {
      exercise_id: 2,
      exercise_type: 'distance',
      range: '6m',
      primary_metric: 'distance',
      metrics: [{ metric: 'distance', has_data: false, data_points: [] }],
    }

    const records: RawRecordsResponse = {
      exercise_id: 2,
      exercise_type: 'distance',
      records: {
        distance: { value: 42.195, entry_id: 15, date: '2026-06-05' },
      },
    }

    const result = transformProgressData(progress, records, 'metric')

    expect(result.records).toHaveLength(1)
    expect(result.records[0]).toEqual({
      label: 'Farthest',
      value: '42.195',
      unit: 'km',
      sub: null,
    })
  })

  it('handles empty data points and empty records', () => {
    const progress: RawProgressResponse = {
      exercise_id: 1,
      exercise_type: 'resistance',
      range: '6m',
      primary_metric: 'weight',
      metrics: [{ metric: 'weight', has_data: false, data_points: [] }],
    }

    const records: RawRecordsResponse = {
      exercise_id: 1,
      exercise_type: 'resistance',
      records: {},
    }

    const result = transformProgressData(progress, records, 'imperial')

    expect(result.series[0]?.points).toEqual([])
    expect(result.volume).toEqual([])
    expect(result.records).toEqual([])
  })

  it('handles missing volume for non-resistance exercises', () => {
    const progress: RawProgressResponse = {
      exercise_id: 3,
      exercise_type: 'timed_hold',
      range: '6m',
      primary_metric: 'duration',
      metrics: [
        {
          metric: 'duration',
          has_data: true,
          data_points: [{ entry_id: 30, date: '2026-06-01', value: 45.0, is_pr: false }],
        },
      ],
    }

    const records: RawRecordsResponse = {
      exercise_id: 3,
      exercise_type: 'timed_hold',
      records: {},
    }

    const result = transformProgressData(progress, records, 'imperial')

    expect(result.series[0]?.points).toHaveLength(1)
    expect(result.volume).toEqual([])
  })

  it('formats duration records with formatDuration', () => {
    const progress: RawProgressResponse = {
      exercise_id: 3,
      exercise_type: 'timed_hold',
      range: '6m',
      primary_metric: 'duration',
      metrics: [{ metric: 'duration', has_data: false, data_points: [] }],
    }

    const records: RawRecordsResponse = {
      exercise_id: 3,
      exercise_type: 'timed_hold',
      records: {
        duration: { value: 125, entry_id: 12, date: '2026-06-10' },
      },
    }

    const result = transformProgressData(progress, records, 'imperial')

    expect(result.records).toHaveLength(1)
    expect(result.records[0]).toEqual({
      label: 'Longest hold',
      value: '02:05',
      unit: '',
      sub: null,
    })
  })

  it('skips records with null values', () => {
    const progress: RawProgressResponse = {
      exercise_id: 1,
      exercise_type: 'resistance',
      range: '6m',
      primary_metric: 'weight',
      metrics: [{ metric: 'weight', has_data: false, data_points: [] }],
    }

    const records: RawRecordsResponse = {
      exercise_id: 1,
      exercise_type: 'resistance',
      records: {
        weight: { value: 225.0, entry_id: 5, date: '2026-05-20' },
        reps: { value: null, entry_id: null, date: null },
        volume: { value: 2700.0, entry_id: 9, date: '2026-06-10' },
      },
    }

    const result = transformProgressData(progress, records, 'imperial')

    expect(result.records).toHaveLength(2)
    expect(result.records.map(r => r.label)).not.toContain('Most reps')
  })

  it('includes exercise type in result', () => {
    const progress: RawProgressResponse = {
      exercise_id: 1,
      exercise_type: 'interval',
      range: '6m',
      primary_metric: 'completed_rounds',
      metrics: [{ metric: 'completed_rounds', has_data: false, data_points: [] }],
    }

    const records: RawRecordsResponse = {
      exercise_id: 1,
      exercise_type: 'interval',
      records: {},
    }

    const result = transformProgressData(progress, records, 'imperial')

    expect(result.type).toBe('interval')
  })

  it('passes the API primary_metric through to primaryMetric', () => {
    const progress: RawProgressResponse = {
      exercise_id: 1,
      exercise_type: 'resistance',
      range: '6m',
      primary_metric: 'reps',
      metrics: [{ metric: 'reps', has_data: false, data_points: [] }],
    }

    const records: RawRecordsResponse = {
      exercise_id: 1,
      exercise_type: 'resistance',
      records: {},
    }

    const result = transformProgressData(progress, records, 'imperial')

    expect(result.primaryMetric).toBe('reps')
  })

  it('handles unknown record keys with fallback label', () => {
    const progress: RawProgressResponse = {
      exercise_id: 1,
      exercise_type: 'resistance',
      range: '6m',
      primary_metric: 'weight',
      metrics: [{ metric: 'weight', has_data: false, data_points: [] }],
    }

    const records: RawRecordsResponse = {
      exercise_id: 1,
      exercise_type: 'resistance',
      records: {
        custom_metric: { value: 42, entry_id: 1, date: '2026-06-01' },
      },
    }

    const result = transformProgressData(progress, records, 'imperial')

    expect(result.records).toHaveLength(1)
    expect(result.records[0]?.label).toBe('custom_metric')
  })
})

describe('transformProgressData with multiple metric series', () => {
  it('maps every series from the distance fixture', () => {
    const result = transformProgressData(
      progressDistance.data as RawProgressResponse,
      recordsDistance.data as RawRecordsResponse,
      'imperial'
    )

    expect(result.series.map(s => s.metric)).toEqual(['distance', 'duration'])
    expect(result.primaryMetric).toBe('distance')

    const duration = result.series.find(s => s.metric === 'duration')
    expect(duration?.hasData).toBe(true)
    expect(duration?.points.map(p => p.value)).toEqual([1500, 2100, 2400])
  })

  it('keeps a series that has all-time data but no points in range', () => {
    const progress: RawProgressResponse = {
      exercise_id: 1,
      exercise_type: 'distance',
      range: '1m',
      primary_metric: 'distance',
      metrics: [{ metric: 'duration', has_data: true, data_points: [] }],
    }

    const result = transformProgressData(
      progress,
      { exercise_id: 1, exercise_type: 'distance', records: {} },
      'imperial'
    )

    expect(result.series[0]?.hasData).toBe(true)
    expect(result.series[0]?.points).toEqual([])
  })

  it('labels a duration record as time outside a timed hold', () => {
    const progress: RawProgressResponse = {
      exercise_id: 1,
      exercise_type: 'distance',
      range: 'all',
      primary_metric: 'distance',
      metrics: [],
    }

    const records: RawRecordsResponse = {
      exercise_id: 1,
      exercise_type: 'distance',
      records: { duration: { value: 2400, entry_id: 5, date: '2026-08-22' } },
    }

    expect(transformProgressData(progress, records, 'imperial').records[0]).toEqual({
      label: 'Longest time',
      value: '40:00',
      unit: '',
      sub: null,
    })
  })
})

describe('extractAllTimeBest', () => {
  it('returns reps value for resistance exercises', () => {
    const records: RawRecordsResponse = {
      exercise_id: 1,
      exercise_type: 'resistance',
      records: {
        weight: { value: 225.0, entry_id: 5, date: '2026-05-20' },
        reps: { value: 15, entry_id: 8, date: '2026-06-10' },
      },
    }

    const result = extractAllTimeBest(records, 'resistance')

    expect(result).toBe(15)
  })

  it('returns duration value for timed_hold exercises', () => {
    const records: RawRecordsResponse = {
      exercise_id: 3,
      exercise_type: 'timed_hold',
      records: {
        duration: { value: 185, entry_id: 12, date: '2026-06-10' },
      },
    }

    const result = extractAllTimeBest(records, 'timed_hold')

    expect(result).toBe(185)
  })

  it('returns distance value for distance exercises', () => {
    const records: RawRecordsResponse = {
      exercise_id: 2,
      exercise_type: 'distance',
      records: {
        distance: { value: 42.195, entry_id: 15, date: '2026-06-05' },
      },
    }

    const result = extractAllTimeBest(records, 'distance')

    expect(result).toBe(42.195)
  })

  it('returns completed_rounds value for interval exercises', () => {
    const records: RawRecordsResponse = {
      exercise_id: 4,
      exercise_type: 'interval',
      records: {
        completed_rounds: { value: 8, entry_id: 20, date: '2026-06-15' },
      },
    }

    const result = extractAllTimeBest(records, 'interval')

    expect(result).toBe(8)
  })

  it('returns null when matching record does not exist', () => {
    const records: RawRecordsResponse = {
      exercise_id: 1,
      exercise_type: 'resistance',
      records: {
        weight: { value: 225.0, entry_id: 5, date: '2026-05-20' },
      },
    }

    const result = extractAllTimeBest(records, 'resistance')

    expect(result).toBeNull()
  })

  it('returns null when record value is null', () => {
    const records: RawRecordsResponse = {
      exercise_id: 1,
      exercise_type: 'resistance',
      records: {
        reps: { value: null, entry_id: null, date: null },
      },
    }

    const result = extractAllTimeBest(records, 'resistance')

    expect(result).toBeNull()
  })

  it('returns zero when record value is zero', () => {
    const records: RawRecordsResponse = {
      exercise_id: 1,
      exercise_type: 'resistance',
      records: {
        reps: { value: 0, entry_id: 1, date: '2026-06-01' },
      },
    }

    const result = extractAllTimeBest(records, 'resistance')

    expect(result).toBe(0)
  })
})
