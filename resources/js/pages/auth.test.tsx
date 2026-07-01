import { describe, it, expect } from 'vitest'
import { screen, renderWithProviders, userEvent } from '@/test/render'
import { server } from '@/test/server'
import { http } from 'msw'
import { errorEnvelope } from '@/test/mocks/handlers/_helpers'
import { LoginPage, RegisterPage } from './auth'

describe('LoginPage', () => {
  it('logs in successfully and sets token in localStorage', async () => {
    const user = userEvent.setup()
    renderWithProviders(<LoginPage />, { user: null })

    const emailInput = screen.getByLabelText('Email')
    const passwordInput = screen.getByLabelText('Password')
    const submitButton = screen.getByRole('button', { name: /log in/i })

    await user.type(emailInput, 'test@example.com')
    await user.type(passwordInput, 'password123')
    await user.click(submitButton)

    // Verify token was set in localStorage (setToken is called by login())
    expect(localStorage.getItem('ps_token')).toBe('test-access-token')

    // Verify no error message is displayed
    const errorElements = screen.queryAllByText(/something went wrong/i)
    expect(errorElements).toHaveLength(0)
  })

  it('displays validation error message when credentials are invalid', async () => {
    const user = userEvent.setup()

    // Override the login handler to return a 422 validation error
    server.use(
      http.post('/api/v1/login', () =>
        errorEnvelope(422, 'The given data was invalid.', {
          email: ['These credentials do not match our records.'],
        })
      )
    )

    renderWithProviders(<LoginPage />, { user: null })

    const emailInput = screen.getByLabelText('Email')
    const passwordInput = screen.getByLabelText('Password')
    const submitButton = screen.getByRole('button', { name: /log in/i })

    await user.type(emailInput, 'invalid@example.com')
    await user.type(passwordInput, 'wrongpassword')
    await user.click(submitButton)

    // Verify error message is displayed
    const errorMessage = await screen.findByText('These credentials do not match our records.')
    expect(errorMessage).toBeInTheDocument()

    // Verify token was not set
    expect(localStorage.getItem('ps_token')).toBeNull()
  })
})

describe('RegisterPage', () => {
  it('creates account successfully with required fields and sets token in localStorage', async () => {
    const user = userEvent.setup()
    renderWithProviders(<RegisterPage />, { user: null })

    const emailInput = screen.getByLabelText('Email')
    const passwordInput = screen.getByLabelText('Password')
    const confirmInput = screen.getByLabelText('Confirm')
    const submitButton = screen.getByRole('button', { name: /create account/i })

    // Select measurement system (imperial)
    const imperialButton = screen.getByRole('tab', { name: /imperial/i })
    await user.click(imperialButton)

    // Fill in required fields
    await user.type(emailInput, 'newuser@example.com')
    await user.type(passwordInput, 'password123')
    await user.type(confirmInput, 'password123')

    // Submit the form
    await user.click(submitButton)

    // Verify token was set in localStorage
    expect(localStorage.getItem('ps_token')).toBe('test-access-token')

    // Verify no error message is displayed
    const errorElements = screen.queryAllByText(/something went wrong/i)
    expect(errorElements).toHaveLength(0)
  })

  it('allows registering with optional first and last name fields', async () => {
    const user = userEvent.setup()
    renderWithProviders(<RegisterPage />, { user: null })

    const firstNameInput = screen.getByLabelText('First name')
    const lastNameInput = screen.getByLabelText('Last name')
    const emailInput = screen.getByLabelText('Email')
    const passwordInput = screen.getByLabelText('Password')
    const confirmInput = screen.getByLabelText('Confirm')
    const metricButton = screen.getByRole('tab', { name: /metric/i })
    const submitButton = screen.getByRole('button', { name: /create account/i })

    // Fill in all fields including optional ones
    await user.type(firstNameInput, 'Justin')
    await user.type(lastNameInput, 'Christenson')
    await user.type(emailInput, 'justin@example.com')
    await user.type(passwordInput, 'password123')
    await user.type(confirmInput, 'password123')
    await user.click(metricButton)

    // Submit the form
    await user.click(submitButton)

    // Verify token was set in localStorage
    expect(localStorage.getItem('ps_token')).toBe('test-access-token')

    // Verify no error message is displayed
    const errorElements = screen.queryAllByText(/something went wrong/i)
    expect(errorElements).toHaveLength(0)
  })
})
