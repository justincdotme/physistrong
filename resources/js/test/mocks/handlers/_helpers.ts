import { HttpResponse } from 'msw'

export function errorEnvelope(status: number, message: string, errors?: Record<string, string[]>) {
  return HttpResponse.json(errors ? { message, errors } : { message }, { status })
}
