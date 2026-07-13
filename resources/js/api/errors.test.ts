import { describe, it, expect } from 'vitest'
import { AxiosError, AxiosHeaders } from 'axios'
import { extractFieldErrors, extractMessage } from './errors'

function make422(errors: Record<string, string[]>): AxiosError {
  const headers = new AxiosHeaders()
  return new AxiosError('Validation failed', 'ERR_BAD_REQUEST', undefined, undefined, {
    status: 422,
    statusText: 'Unprocessable Entity',
    headers,
    config: { headers },
    data: { message: 'The given data was invalid.', errors },
  })
}

function makeAxiosError(status: number, data: unknown): AxiosError {
  const headers = new AxiosHeaders()
  return new AxiosError('Request failed', 'ERR_BAD_REQUEST', undefined, undefined, {
    status,
    statusText: 'Error',
    headers,
    config: { headers },
    data,
  })
}

describe('extractFieldErrors', () => {
  it.each([
    ['non-axios error', new Error('network'), {}],
    ['non-422 axios error', makeAxiosError(500, { errors: { name: ['bad'] } }), {}],
    ['422 with no errors key', makeAxiosError(422, { message: 'fail' }), {}],
  ])('returns empty record for %s', (_label, error, expected) => {
    expect(extractFieldErrors(error)).toEqual(expected)
  })

  it('returns first message per field from a 422 response', () => {
    const error = make422({
      email: ['Email is required.', 'Email must be valid.'],
      password: ['Password is too short.'],
    })
    expect(extractFieldErrors(error)).toEqual({
      email: 'Email is required.',
      password: 'Password is too short.',
    })
  })

  it('skips fields with empty message arrays', () => {
    const error = make422({ email: ['Required.'], name: [] })
    expect(extractFieldErrors(error)).toEqual({ email: 'Required.' })
  })
})

describe('extractMessage', () => {
  it('returns the API message from an axios error response', () => {
    const error = makeAxiosError(400, { message: 'Bad request.' })
    expect(extractMessage(error)).toBe('Bad request.')
  })

  it('returns fallback for non-axios errors', () => {
    expect(extractMessage(new Error('boom'))).toBe('Something went wrong. Try again.')
  })

  it('returns fallback when response has no message', () => {
    const error = makeAxiosError(500, {})
    expect(extractMessage(error)).toBe('Something went wrong. Try again.')
  })
})
