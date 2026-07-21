import { describe, it, expect } from 'vitest'
import { screen, renderWithProviders, userEvent } from '@/test/render'
import { server } from '@/test/server'
import { http } from 'msw'
import { errorEnvelope } from '@/test/mocks/handlers/_helpers'
import { LoginPage, RegisterPage } from './auth'

const workoutsStub = { path: '/workouts', element: <div>Workouts landing</div> }

describe('LoginPage', () => {
  it('logs in successfully and redirects to workouts', async () => {
    const user = userEvent.setup()
    renderWithProviders(<LoginPage />, {
      user: null,
      route: '/login',
      path: '/login',
      additionalRoutes: [workoutsStub],
    })

    await user.type(screen.getByLabelText('Email'), 'test@example.com')
    await user.type(screen.getByLabelText('Password'), 'password123')
    await user.click(screen.getByRole('button', { name: /log in/i }))

    expect(await screen.findByText('Workouts landing')).toBeInTheDocument()
  })

  it('displays validation error message when credentials are invalid', async () => {
    const user = userEvent.setup()

    server.use(
      http.post('/api/v1/login', () =>
        errorEnvelope(422, 'The given data was invalid.', {
          email: ['These credentials do not match our records.'],
        })
      )
    )

    renderWithProviders(<LoginPage />, {
      user: null,
      route: '/login',
      path: '/login',
      additionalRoutes: [workoutsStub],
    })

    await user.type(screen.getByLabelText('Email'), 'invalid@example.com')
    await user.type(screen.getByLabelText('Password'), 'wrongpassword')
    await user.click(screen.getByRole('button', { name: /log in/i }))

    expect(
      await screen.findByText('These credentials do not match our records.')
    ).toBeInTheDocument()
    expect(screen.queryByText('Workouts landing')).not.toBeInTheDocument()
  })
})

describe('RegisterPage', () => {
  it('creates account successfully with required fields and redirects to workouts', async () => {
    const user = userEvent.setup()
    renderWithProviders(<RegisterPage />, {
      user: null,
      route: '/register',
      path: '/register',
      additionalRoutes: [workoutsStub],
    })

    await user.click(screen.getByRole('tab', { name: /imperial/i }))
    await user.type(screen.getByLabelText('Email'), 'newuser@example.com')
    await user.type(screen.getByLabelText('Password'), 'password123')
    await user.type(screen.getByLabelText('Confirm'), 'password123')
    await user.click(screen.getByRole('button', { name: /create account/i }))

    expect(await screen.findByText('Workouts landing')).toBeInTheDocument()
  })

  it('allows registering with optional first and last name fields', async () => {
    const user = userEvent.setup()
    renderWithProviders(<RegisterPage />, {
      user: null,
      route: '/register',
      path: '/register',
      additionalRoutes: [workoutsStub],
    })

    await user.type(screen.getByLabelText('First name'), 'Justin')
    await user.type(screen.getByLabelText('Last name'), 'Christenson')
    await user.type(screen.getByLabelText('Email'), 'justin@example.com')
    await user.type(screen.getByLabelText('Password'), 'password123')
    await user.type(screen.getByLabelText('Confirm'), 'password123')
    await user.click(screen.getByRole('tab', { name: /metric/i }))
    await user.click(screen.getByRole('button', { name: /create account/i }))

    expect(await screen.findByText('Workouts landing')).toBeInTheDocument()
  })
})
