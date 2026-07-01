import { http, HttpResponse } from 'msw'
import listResponse from '../fixtures/equipment/list.json'
import createdResponse from '../fixtures/equipment/created.json'
import inUseError from '../fixtures/equipment/in-use-error.json'

export const equipmentHandlers = [
  http.get('/api/v1/equipment-types', () => {
    return HttpResponse.json(listResponse)
  }),

  http.post('/api/v1/equipment-types', () => {
    return HttpResponse.json(createdResponse, { status: 201 })
  }),

  http.delete('/api/v1/equipment-types/:id', ({ params }) => {
    if (params.id === '9002') {
      return HttpResponse.json(inUseError, { status: 409 })
    }

    return new HttpResponse(null, { status: 204 })
  }),
]
