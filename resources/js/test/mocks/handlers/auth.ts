import { http, HttpResponse } from 'msw'
import { errorEnvelope } from './_helpers'
import loginResponse from '../fixtures/auth/login.json'
import registerResponse from '../fixtures/auth/register.json'

export const authHandlers = [
  http.post('/api/v1/login', async ({ request }) => {
    const body = (await request.json()) as Record<string, unknown>

    if (body.email === 'invalid@sentinel.test') {
      return errorEnvelope(422, 'The given data was invalid.', {
        email: ['These credentials do not match our records.'],
      })
    }

    return HttpResponse.json(loginResponse)
  }),

  http.post('/api/v1/register', () => {
    return HttpResponse.json(registerResponse, { status: 201 })
  }),

  http.post('/api/v1/logout', () => {
    return new HttpResponse(null, { status: 204 })
  }),

  http.post('/api/v1/password/forgot', () => {
    return HttpResponse.json({})
  }),

  http.post('/api/v1/password/reset', () => {
    return HttpResponse.json({})
  }),
]
