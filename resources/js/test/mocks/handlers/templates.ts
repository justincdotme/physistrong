import { http, HttpResponse } from 'msw'
import templateList from '../fixtures/templates/list.json'
import templateShow from '../fixtures/templates/show.json'
import templateCreated from '../fixtures/templates/created.json'
import templateCloned from '../fixtures/templates/cloned.json'

export const templateHandlers = [
  http.get('/api/v1/templates', () => {
    return HttpResponse.json(templateList)
  }),

  http.get('/api/v1/templates/:id', () => {
    return HttpResponse.json(templateShow)
  }),

  http.post('/api/v1/templates', () => {
    return HttpResponse.json(templateCreated, { status: 201 })
  }),

  http.put('/api/v1/templates/:id', () => {
    return HttpResponse.json(templateShow)
  }),

  http.delete('/api/v1/templates/:id', () => {
    return new HttpResponse(null, { status: 204 })
  }),

  http.post('/api/v1/templates/:id/exercises', () => {
    return HttpResponse.json(templateShow, { status: 201 })
  }),

  http.delete('/api/v1/templates/:id/exercises/:exerciseId', () => {
    return new HttpResponse(null, { status: 204 })
  }),

  http.put('/api/v1/templates/:id/exercises/reorder', () => {
    return HttpResponse.json(templateShow)
  }),

  http.post('/api/v1/templates/:id/clone', () => {
    return HttpResponse.json(templateCloned, { status: 201 })
  }),

  http.post('/api/v1/templates/:id/groups', () => {
    return HttpResponse.json(templateShow, { status: 201 })
  }),

  http.delete('/api/v1/templates/:id/groups/:groupId', () => {
    return new HttpResponse(null, { status: 204 })
  }),

  http.post('/api/v1/templates/:id/groups/:groupId/exercises', () => {
    return HttpResponse.json(templateShow)
  }),
]
