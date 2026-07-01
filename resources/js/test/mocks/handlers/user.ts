import { http, HttpResponse } from 'msw'
import userProfile from '../fixtures/user/profile.json'
import userProfileUpdated from '../fixtures/user/profile-updated.json'
import validationError from '../fixtures/user/validation-error.json'

export const userHandlers = [
  http.get('/api/v1/user', () => {
    return HttpResponse.json(userProfile)
  }),

  http.put('/api/v1/user', async ({ request }) => {
    const body = (await request.json()) as Record<string, unknown>

    // Sentinel email triggers validation error. Uses invalid@sentinel.test for consistency with auth handler.
    if (body.email === 'invalid@sentinel.test') {
      return HttpResponse.json(validationError, { status: 422 })
    }

    return HttpResponse.json(userProfileUpdated)
  }),
]
