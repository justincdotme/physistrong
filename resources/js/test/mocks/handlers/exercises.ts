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
import progressDistance from '../fixtures/progress/progress-distance.json'
import recordsDistance from '../fixtures/progress/records-distance.json'
import exerciseDistance from '../fixtures/exercises/show-distance.json'

const FORBIDDEN_ID = '9001'
const IN_USE_ID = '9002'

// Derived from the fixture so a recapture cannot desynchronize the routing.
const DISTANCE_ID = String(exerciseDistance.data.id)

export const exerciseHandlers = [
  http.get('/api/v1/exercises', () => HttpResponse.json(exerciseList)),

  http.get('/api/v1/exercises/:id', ({ params }) => {
    if (params.id === FORBIDDEN_ID) {
      return HttpResponse.json(exerciseForbidden, { status: 403 })
    }
    if (params.id === DISTANCE_ID) {
      return HttpResponse.json(exerciseDistance)
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

  http.get('/api/v1/exercises/:id/progress', ({ params }) => {
    return HttpResponse.json(params.id === DISTANCE_ID ? progressDistance : progressResistance)
  }),

  http.get('/api/v1/exercises/:id/records', ({ params }) => {
    return HttpResponse.json(params.id === DISTANCE_ID ? recordsDistance : recordsResistance)
  }),
]
