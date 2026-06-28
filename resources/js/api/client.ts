import axios from 'axios'

const TOKEN_KEY = 'ps_token'

export const api = axios.create({
  baseURL: '/api/v1',
  headers: { Accept: 'application/json' },
})

api.interceptors.request.use(config => {
  const token = localStorage.getItem(TOKEN_KEY)
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  return config
})

api.interceptors.response.use(
  response => response,
  error => {
    if (error.response?.status === 401 && localStorage.getItem(TOKEN_KEY)) {
      localStorage.removeItem(TOKEN_KEY)
      window.location.href = '/login'
    }
    return Promise.reject(error)
  }
)

export function setToken(token: string): void {
  localStorage.setItem(TOKEN_KEY, token)
}

export function clearToken(): void {
  localStorage.removeItem(TOKEN_KEY)
}

export function hasToken(): boolean {
  return !!localStorage.getItem(TOKEN_KEY)
}
