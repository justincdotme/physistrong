import axios from 'axios'

export const api = axios.create({
  baseURL: '/api/v1',
  headers: { Accept: 'application/json' },
})

// The token lives in an HttpOnly cookie the browser attaches on its own, so
// this flag is the only client-side auth signal. It distinguishes an expired
// session (redirect to login) from a plain guest 401 (no redirect).
let authenticated = false

export function markAuthenticated(): void {
  authenticated = true
}

export function markUnauthenticated(): void {
  authenticated = false
}

api.interceptors.response.use(
  response => response,
  error => {
    if (error.response?.status === 401 && authenticated) {
      authenticated = false
      window.location.href = '/login'
    }
    return Promise.reject(error)
  }
)
