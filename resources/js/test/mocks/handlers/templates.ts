import { http, HttpResponse } from 'msw'
import templateList from '../fixtures/templates/list.json'
import templateShow from '../fixtures/templates/show.json'
import templateCreated from '../fixtures/templates/created.json'
import templateCloned from '../fixtures/templates/cloned.json'

export const templateHandlers = [
  // GET /api/v1/templates
  http.get('/api/v1/templates', () => {
    return HttpResponse.json(templateList)
  }),

  // GET /api/v1/templates/:id
  http.get('/api/v1/templates/:id', () => {
    return HttpResponse.json(templateShow)
  }),

  // POST /api/v1/templates
  http.post('/api/v1/templates', () => {
    return HttpResponse.json(templateCreated, { status: 201 })
  }),

  // PUT /api/v1/templates/:id
  http.put('/api/v1/templates/:id', () => {
    return HttpResponse.json(templateShow)
  }),

  // DELETE /api/v1/templates/:id
  http.delete('/api/v1/templates/:id', () => {
    return new HttpResponse(null, { status: 204 })
  }),

  // POST /api/v1/templates/:id/exercises
  http.post('/api/v1/templates/:id/exercises', () => {
    return HttpResponse.json(templateShow, { status: 201 })
  }),

  // DELETE /api/v1/templates/:id/exercises/:exerciseId
  http.delete('/api/v1/templates/:id/exercises/:exerciseId', () => {
    return new HttpResponse(null, { status: 204 })
  }),

  // PUT /api/v1/templates/:id/exercises/reorder
  http.put('/api/v1/templates/:id/exercises/reorder', () => {
    return HttpResponse.json(templateShow)
  }),

  // POST /api/v1/templates/:id/clone
  http.post('/api/v1/templates/:id/clone', () => {
    return HttpResponse.json(templateCloned, { status: 201 })
  }),

  // POST /api/v1/templates/:id/groups
  http.post('/api/v1/templates/:id/groups', () => {
    return HttpResponse.json(templateShow, { status: 201 })
  }),

  // DELETE /api/v1/templates/:id/groups/:groupId
  http.delete('/api/v1/templates/:id/groups/:groupId', () => {
    return new HttpResponse(null, { status: 204 })
  }),

  // POST /api/v1/templates/:id/groups/:groupId/exercises
  http.post('/api/v1/templates/:id/groups/:groupId/exercises', () => {
    return HttpResponse.json(templateShow)
  }),

  // DELETE /api/v1/templates/:id/groups/:groupId/exercises/:exerciseId
  http.delete('/api/v1/templates/:id/groups/:groupId/exercises/:exerciseId', () => {
    return HttpResponse.json(templateShow)
  }),
]
