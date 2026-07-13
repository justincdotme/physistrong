import { describe, it, expect, beforeEach, vi } from 'vitest'
import { render } from '@testing-library/react'
import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { MemoryRouter, Routes, Route } from 'react-router-dom'
import { screen, renderWithProviders, userEvent } from '@/test/render'
import { server } from '@/test/server'
import { http, HttpResponse } from 'msw'
import { AppProvider } from '@/lib/store'
import { AuthProvider } from '@/lib/auth-provider'
import { ProfilePage } from './profile'

describe('ProfilePage', () => {
  beforeEach(() => {
    localStorage.clear()
  })

  describe('renders authenticated user profile', () => {
    it('displays user information from AuthContext', () => {
      renderWithProviders(<ProfilePage />)

      expect(screen.getByText('Test User')).toBeInTheDocument()
      expect(screen.getByText('test@example.com')).toBeInTheDocument()
      expect(screen.getByDisplayValue('Test')).toBeInTheDocument()
      expect(screen.getByDisplayValue('User')).toBeInTheDocument()
      expect(screen.getByDisplayValue('test@example.com')).toBeInTheDocument()
    })

    it('displays measurement system and theme preferences', () => {
      renderWithProviders(<ProfilePage />)

      const imperialTab = screen.getByRole('tab', { name: 'Imperial (lb, mi)' })
      const lightTab = screen.getByRole('tab', { name: 'Light' })
      expect(imperialTab).toHaveAttribute('aria-selected', 'true')
      expect(lightTab).toHaveAttribute('aria-selected', 'true')
    })
  })

  describe('update profile', () => {
    it('sends update request when fields are changed and save is clicked', async () => {
      const user = userEvent.setup()
      let updatePayload: Record<string, unknown> = {}

      server.use(
        http.put('/api/v1/user', async ({ request }) => {
          const data = await request.json()
          updatePayload = data as Record<string, unknown>
          return HttpResponse.json({
            data: {
              id: 1,
              email: updatePayload.email,
              first_name: updatePayload.first_name,
              last_name: updatePayload.last_name,
              measurement_system: 'imperial',
              theme: 'light',
              created_at: '2026-01-01T00:00:00.000000Z',
              updated_at: '2026-01-01T00:00:00.000000Z',
            },
          })
        })
      )

      renderWithProviders(<ProfilePage />)

      const firstNameInput = screen.getByDisplayValue('Test') as HTMLInputElement
      const lastNameInput = screen.getByDisplayValue('User') as HTMLInputElement

      // Change fields to make them dirty
      await user.clear(firstNameInput)
      await user.type(firstNameInput, 'Claude')
      await user.clear(lastNameInput)
      await user.type(lastNameInput, 'Ai')

      // Save Changes button appears when dirty
      const saveButton = screen.getByRole('button', { name: /save changes/i })
      expect(saveButton).toBeInTheDocument()

      await user.click(saveButton)

      await new Promise(resolve => setTimeout(resolve, 100))

      expect(updatePayload).toEqual({
        first_name: 'Claude',
        last_name: 'Ai',
        email: 'test@example.com',
      })
    })

    it('handles validation errors on 422 response', async () => {
      const user = userEvent.setup()

      renderWithProviders(<ProfilePage />)

      const emailInput = screen.getByDisplayValue('test@example.com') as HTMLInputElement

      // Use sentinel email to trigger 422 error
      await user.clear(emailInput)
      await user.type(emailInput, 'invalid@sentinel.test')

      const saveButton = screen.getByRole('button', { name: /save changes/i })
      await user.click(saveButton)

      // The 422 field error surfaces through the toast, not inline.
      expect(
        await screen.findByText('The selected measurement system is invalid.')
      ).toBeInTheDocument()
      expect(emailInput.value).toBe('invalid@sentinel.test')
    })
  })

  describe('logout', () => {
    it('calls logout handler when logout button is clicked', async () => {
      const handleLogout = vi.fn()
      const user = userEvent.setup()

      renderWithProviders(<ProfilePage />, { handleLogout })

      const logoutButton = screen.getByRole('button', { name: /log out/i })
      await user.click(logoutButton)

      expect(handleLogout).toHaveBeenCalled()
    })

    it('shows error toast and stays on profile when logout returns 500', async () => {
      server.use(
        http.post('/api/v1/logout', () => {
          return new HttpResponse(null, { status: 500 })
        })
      )

      const user = userEvent.setup()
      const queryClient = new QueryClient({
        defaultOptions: { queries: { retry: false }, mutations: { retry: false } },
      })

      render(
        <QueryClientProvider client={queryClient}>
          <MemoryRouter initialEntries={['/profile']}>
            <AppProvider>
              <AuthProvider>
                <Routes>
                  <Route path="/profile" element={<ProfilePage />} />
                  <Route path="/login" element={<div>Login Page</div>} />
                </Routes>
              </AuthProvider>
            </AppProvider>
          </MemoryRouter>
        </QueryClientProvider>
      )

      // AuthProvider loads user via getProfile on mount
      await screen.findByRole('button', { name: /log out/i })

      const logoutButton = screen.getByRole('button', { name: /log out/i })
      await user.click(logoutButton)

      expect(await screen.findByText('Could not log out. Try again.')).toBeInTheDocument()
      expect(screen.queryByText('Login Page')).not.toBeInTheDocument()
      expect(screen.getByRole('button', { name: /log out/i })).toBeInTheDocument()
    })
  })
})
