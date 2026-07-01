import { http, HttpResponse } from 'msw'
import listPage1 from '../fixtures/workouts/list-page-1.json'
import listPage2 from '../fixtures/workouts/list-page-2.json'
import show from '../fixtures/workouts/show.json'
import created from '../fixtures/workouts/created.json'
import forbidden from '../fixtures/workouts/forbidden.json'
import copied from '../fixtures/workouts/copied.json'

export const workoutHandlers = [
  // GET /api/v1/workouts - paginated list
  http.get('/api/v1/workouts', ({ request }) => {
    const pageParam = new URL(request.url).searchParams.get('page')
    if (pageParam === '2') {
      return HttpResponse.json(listPage2)
    }
    return HttpResponse.json(listPage1)
  }),

  // GET /api/v1/workouts/:id
  http.get('/api/v1/workouts/:id', ({ params }) => {
    if (params.id === '9001') {
      return HttpResponse.json(forbidden, { status: 403 })
    }
    return HttpResponse.json(show)
  }),

  // POST /api/v1/workouts
  http.post('/api/v1/workouts', () => {
    return HttpResponse.json(created, { status: 201 })
  }),

  // PUT /api/v1/workouts/:id
  http.put('/api/v1/workouts/:id', () => {
    return HttpResponse.json(show)
  }),

  // DELETE /api/v1/workouts/:id
  http.delete('/api/v1/workouts/:id', () => {
    return new HttpResponse(null, { status: 204 })
  }),

  // POST /api/v1/workouts/:id/exercises
  http.post('/api/v1/workouts/:id/exercises', () => {
    return HttpResponse.json(show, { status: 201 })
  }),

  // DELETE /api/v1/workouts/:id/exercises/:exerciseId
  http.delete('/api/v1/workouts/:id/exercises/:exerciseId', () => {
    return new HttpResponse(null, { status: 204 })
  }),

  // PUT /api/v1/workouts/:id/exercises/reorder
  http.put('/api/v1/workouts/:id/exercises/reorder', () => {
    return HttpResponse.json(show)
  }),

  // POST /api/v1/workouts/:id/entries
  http.post('/api/v1/workouts/:id/entries', () => {
    const sampleEntry = show.data.entries[0]
    return HttpResponse.json({ data: sampleEntry }, { status: 201 })
  }),

  // PUT /api/v1/workouts/:id/entries/:entryId
  http.put('/api/v1/workouts/:id/entries/:entryId', () => {
    const sampleEntry = show.data.entries[0]
    return HttpResponse.json({ data: sampleEntry })
  }),

  // DELETE /api/v1/workouts/:id/entries/:entryId
  http.delete('/api/v1/workouts/:id/entries/:entryId', () => {
    return new HttpResponse(null, { status: 204 })
  }),

  // PUT /api/v1/workouts/:id/entries/reorder
  http.put('/api/v1/workouts/:id/entries/reorder', () => {
    return HttpResponse.json({ data: show.data.entries })
  }),

  // POST /api/v1/workouts/:id/groups
  http.post('/api/v1/workouts/:id/groups', () => {
    return HttpResponse.json(show, { status: 201 })
  }),

  // DELETE /api/v1/workouts/:id/groups/:groupId
  http.delete('/api/v1/workouts/:id/groups/:groupId', () => {
    return new HttpResponse(null, { status: 204 })
  }),

  // POST /api/v1/workouts/:id/groups/:groupId/entries
  http.post('/api/v1/workouts/:id/groups/:groupId/entries', () => {
    return HttpResponse.json(show)
  }),

  // DELETE /api/v1/workouts/:id/groups/:groupId/entries/:entryId
  http.delete('/api/v1/workouts/:id/groups/:groupId/entries/:entryId', () => {
    return new HttpResponse(null, { status: 204 })
  }),

  // POST /api/v1/workouts/:id/copy
  http.post('/api/v1/workouts/:id/copy', () => {
    return HttpResponse.json(copied, { status: 201 })
  }),
]
