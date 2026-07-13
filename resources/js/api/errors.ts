import { isAxiosError } from 'axios'

export function extractFieldErrors(error: unknown): Record<string, string> {
  if (!isAxiosError(error) || error.response?.status !== 422) return {}
  const fieldErrors = error.response.data?.errors as Record<string, string[]> | undefined
  if (!fieldErrors) return {}
  const result: Record<string, string> = {}
  for (const [key, messages] of Object.entries(fieldErrors)) {
    const first = messages[0]
    if (first) result[key] = first
  }
  return result
}

export function extractMessage(error: unknown): string {
  if (isAxiosError(error) && error.response?.data?.message) {
    return error.response.data.message as string
  }
  return 'Something went wrong. Try again.'
}
