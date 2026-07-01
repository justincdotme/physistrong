import { http, HttpResponse } from 'msw'
import exerciseList from '../fixtures/exercises/list.json'
import exerciseResistance from '../fixtures/exercises/show-resistance.json'
import exerciseForbidden from '../fixtures/exercises/forbidden.json'
import exerciseValidationError from '../fixtures/exercises/validation-error.json'
import exerciseInUseError from '../fixtures/exercises/in-use-error.json'
import createdResistance from '../fixtures/exercises/created-resistance.json'
import createdTimedHold from '../fixtures/exercises/created-timed_hold.json'
import createdDistance from '../fixtures/exercises/created-distance.json'
import createdInterval from '../fixtures/exercises/created-interval.json'
import progressResistance from '../fixtures/progress/progress-resistance.json'
import recordsResistance from '../fixtures/progress/records-resistance.json'

const FORBIDDEN_ID = '9001'
const IN_USE_ID = '9002'

export const exerciseHandlers = [
  http.get('/api/v1/exercises', () => HttpResponse.json(exerciseList)),

  http.get('/api/v1/exercises/:id', ({ params }) => {
    if (params.id === FORBIDDEN_ID) {
      return HttpResponse.json(exerciseForbidden, { status: 403 })
    }
    return HttpResponse.json(exerciseResistance)
  }),

  http.post('/api/v1/exercises', async ({ request }) => {
    const body = (await request.json()) as Record<string, unknown>

    if (!body.name) {
      return HttpResponse.json(exerciseValidationError, { status: 422 })
    }

    const type = body.type as string
    if (type === 'timed_hold') {
      return HttpResponse.json(createdTimedHold, { status: 201 })
    }
    if (type === 'distance') {
      return HttpResponse.json(createdDistance, { status: 201 })
    }
    if (type === 'interval') {
      return HttpResponse.json(createdInterval, { status: 201 })
    }

    return HttpResponse.json(createdResistance, { status: 201 })
  }),

  http.put('/api/v1/exercises/:id', () => {
    return HttpResponse.json(exerciseResistance)
  }),

  http.delete('/api/v1/exercises/:id', ({ params }) => {
    if (params.id === IN_USE_ID) {
      return HttpResponse.json(exerciseInUseError, { status: 409 })
    }
    return new HttpResponse(null, { status: 204 })
  }),

  http.get('/api/v1/exercises/:id/progress', () => {
    return HttpResponse.json(progressResistance)
  }),

  http.get('/api/v1/exercises/:id/records', () => {
    return HttpResponse.json(recordsResistance)
  }),
]
