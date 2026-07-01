import { type ReactElement } from 'react'
import { render } from '@testing-library/react'
import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { MemoryRouter, Routes, Route } from 'react-router-dom'
import { vi } from 'vitest'
import { AppProvider } from '@/lib/store'
import { AuthContext } from '@/lib/auth-context'
import type { User } from '@/api/types'

export const testUser: User = {
  id: '1',
  firstName: 'Test',
  lastName: 'User',
  email: 'test@example.com',
  measurementSystem: 'imperial',
  theme: 'light',
}

interface Options {
  user?: User | null
  route?: string
  path?: string
  handleLogout?: () => Promise<void>
  /**
   * Extra routes to register alongside `path`, for tests that trigger a
   * `navigate()` away from the page under test (e.g. after a delete or
   * create mutation). Without these, react-router logs "No routes matched
   * location" because the test's MemoryRouter only knows about `path`.
   */
  additionalRoutes?: { path: string; element: ReactElement }[]
}

export function renderWithProviders(
  ui: ReactElement,
  { user = testUser, route = '/', path, handleLogout, additionalRoutes = [] }: Options = {}
) {
  const queryClient = new QueryClient({
    defaultOptions: { queries: { retry: false }, mutations: { retry: false } },
  })
  const auth = {
    user,
    isLoading: false,
    setUser: vi.fn(),
    handleLogout: handleLogout ?? vi.fn(),
  }
  return render(
    <QueryClientProvider client={queryClient}>
      <MemoryRouter initialEntries={[route]}>
        <AuthContext.Provider value={auth}>
          <AppProvider>
            {path ? (
              <Routes>
                <Route path={path} element={ui} />
                {additionalRoutes.map(r => (
                  <Route key={r.path} path={r.path} element={r.element} />
                ))}
              </Routes>
            ) : (
              ui
            )}
          </AppProvider>
        </AuthContext.Provider>
      </MemoryRouter>
    </QueryClientProvider>
  )
}

export { render as defaultRender, screen, waitFor } from '@testing-library/react'
export { default as userEvent } from '@testing-library/user-event'
