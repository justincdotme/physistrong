import { http, HttpResponse } from 'msw'
import listPage1 from '../fixtures/workouts/list-page-1.json'
import listPage2 from '../fixtures/workouts/list-page-2.json'
import show from '../fixtures/workouts/show.json'
import created from '../fixtures/workouts/created.json'
import forbidden from '../fixtures/workouts/forbidden.json'
import copied from '../fixtures/workouts/copied.json'

export const workoutHandlers = [
  http.get('/api/v1/workouts', ({ request }) => {
    const pageParam = new URL(request.url).searchParams.get('page')
    if (pageParam === '2') {
      return HttpResponse.json(listPage2)
    }
    return HttpResponse.json(listPage1)
  }),

  http.get('/api/v1/workouts/:id', ({ params }) => {
    if (params.id === '9001') {
      return HttpResponse.json(forbidden, { status: 403 })
    }
    return HttpResponse.json(show)
  }),

  http.post('/api/v1/workouts', () => {
    return HttpResponse.json(created, { status: 201 })
  }),

  http.put('/api/v1/workouts/:id', () => {
    return HttpResponse.json(show)
  }),

  http.delete('/api/v1/workouts/:id', () => {
    return new HttpResponse(null, { status: 204 })
  }),

  http.post('/api/v1/workouts/:id/exercises', () => {
    return HttpResponse.json(show, { status: 201 })
  }),

  http.delete('/api/v1/workouts/:id/exercises/:exerciseId', () => {
    return new HttpResponse(null, { status: 204 })
  }),

  http.put('/api/v1/workouts/:id/exercises/reorder', () => {
    return HttpResponse.json(show)
  }),

  http.post('/api/v1/workouts/:id/entries', () => {
    const sampleEntry = show.data.entries[0]
    return HttpResponse.json({ data: sampleEntry }, { status: 201 })
  }),

  http.put('/api/v1/workouts/:id/entries/:entryId', () => {
    const sampleEntry = show.data.entries[0]
    return HttpResponse.json({ data: sampleEntry })
  }),

  http.delete('/api/v1/workouts/:id/entries/:entryId', () => {
    return new HttpResponse(null, { status: 204 })
  }),

  http.put('/api/v1/workouts/:id/entries/reorder', () => {
    return HttpResponse.json({ data: show.data.entries })
  }),

  http.post('/api/v1/workouts/:id/groups', () => {
    return HttpResponse.json(show, { status: 201 })
  }),

  http.delete('/api/v1/workouts/:id/groups/:groupId', () => {
    return new HttpResponse(null, { status: 204 })
  }),

  http.post('/api/v1/workouts/:id/groups/:groupId/entries', () => {
    return HttpResponse.json(show)
  }),

  http.post('/api/v1/workouts/:id/copy', () => {
    return HttpResponse.json(copied, { status: 201 })
  }),
]
