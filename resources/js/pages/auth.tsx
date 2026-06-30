import { type FormEvent, type ReactNode, useState } from 'react'
import { Link, useNavigate, useParams, useSearchParams } from 'react-router-dom'
import { isAxiosError } from 'axios'
import { Button } from '@/components/ui/button'
import { SegmentedControl } from '@/components/ui/segmented-control'
import { useAuth } from '@/hooks/use-auth'
import { login, register, forgotPassword, resetPassword } from '@/api/auth'

interface AuthLayoutProps {
  title: string
  subtitle?: string
  children: ReactNode
  footer?: ReactNode
}

export function AuthLayout({ title, subtitle, children, footer }: AuthLayoutProps) {
  return (
    <div
      dusk="auth-layout"
      className="min-h-[100dvh] flex flex-col items-center justify-center px-5 py-10"
    >
      <div className="w-full max-w-[400px]">
        <div className="flex flex-col items-center mb-7">
          <span
            className="h-14 w-14 rounded-2xl flex items-center justify-center text-white mb-4"
            style={{ backgroundImage: 'var(--gradient-primary)' }}
          >
            <svg width="28" height="28" viewBox="0 0 24 24" fill="currentColor">
              <path d="M6 4c-1.1 0-2 .9-2 2v3h2V6h3V4H6zm12 0v2h3v3h2V6c0-1.1-.9-2-2-2h-3zM4 14v3c0 1.1.9 2 2 2h3v-2H6v-3H4zm16 0v3h-3v2h3c1.1 0 2-.9 2-2v-3h-2z" />
            </svg>
          </span>
          <h1 className="font-extrabold text-2xl tracking-tight">{title}</h1>
          {subtitle && <p className="text-text-secondary text-sm mt-1 text-center">{subtitle}</p>}
        </div>
        <div className="ps-card p-6">{children}</div>
        {footer && <div className="text-center text-sm text-text-secondary mt-5">{footer}</div>}
      </div>
    </div>
  )
}

interface FieldProps {
  label: string
  type?: 'text' | 'email' | 'password'
  value: string
  onChange: (value: string) => void
  placeholder?: string
  name?: string
  error?: string
  readOnly?: boolean
}

export function Field({
  label,
  type = 'text',
  value,
  onChange,
  placeholder,
  name,
  error,
  readOnly,
}: FieldProps) {
  return (
    <label className="block">
      <span className="label-caps text-text-secondary block mb-1.5">{label}</span>
      <input
        type={type}
        name={name}
        value={value}
        placeholder={placeholder}
        readOnly={readOnly}
        onChange={e => onChange(e.target.value)}
        className={`ps-input w-full px-3 py-2.5 text-sm${error ? ' border-destructive' : ''}${readOnly ? ' opacity-60 cursor-not-allowed' : ''}`}
      />
      {error && <p className="text-destructive text-xs mt-1">{error}</p>}
    </label>
  )
}

function extractFieldErrors(error: unknown): Record<string, string> {
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

function extractMessage(error: unknown): string {
  if (isAxiosError(error) && error.response?.data?.message) {
    return error.response.data.message as string
  }
  return 'Something went wrong. Try again.'
}

export function LoginPage() {
  const { setUser } = useAuth()
  const navigate = useNavigate()
  const [email, setEmail] = useState('')
  const [password, setPassword] = useState('')
  const [errors, setErrors] = useState<Record<string, string>>({})
  const [generalError, setGeneralError] = useState('')
  const [submitting, setSubmitting] = useState(false)

  const handleSubmit = async (e: FormEvent) => {
    e.preventDefault()
    setErrors({})
    setGeneralError('')
    setSubmitting(true)
    try {
      const { user } = await login({ email, password })
      setUser(user)
      navigate('/workouts')
    } catch (err) {
      const fieldErrors = extractFieldErrors(err)
      if (Object.keys(fieldErrors).length > 0) {
        setErrors(fieldErrors)
      } else {
        setGeneralError(extractMessage(err))
      }
    } finally {
      setSubmitting(false)
    }
  }

  return (
    <AuthLayout
      title="Welcome back"
      subtitle="Log in to keep your streak going."
      footer={
        <>
          New here?{' '}
          <Link to="/register" className="font-semibold text-primary">
            Create an account
          </Link>
        </>
      }
    >
      <form dusk="login-form" onSubmit={handleSubmit} className="flex flex-col gap-4">
        {generalError && (
          <p dusk="auth-error" className="text-destructive text-sm text-center">
            {generalError}
          </p>
        )}
        <Field
          label="Email"
          type="email"
          name="email"
          value={email}
          onChange={setEmail}
          placeholder="you@example.com"
          error={errors.email}
        />
        <div>
          <Field
            label="Password"
            type="password"
            name="password"
            value={password}
            onChange={setPassword}
            placeholder="••••••••"
            error={errors.password}
          />
          <div className="text-right mt-1.5">
            <Link to="/password/reset" className="text-[13px] text-secondary font-medium">
              Forgot password?
            </Link>
          </div>
        </div>
        <Button type="submit" full size="lg" disabled={submitting}>
          {submitting ? 'Logging in...' : 'Log In'}
        </Button>
      </form>
    </AuthLayout>
  )
}

interface RegisterFormState {
  first: string
  last: string
  email: string
  password: string
  passwordConfirm: string
}

export function RegisterPage() {
  const { setUser } = useAuth()
  const navigate = useNavigate()
  const [form, setForm] = useState<RegisterFormState>({
    first: '',
    last: '',
    email: '',
    password: '',
    passwordConfirm: '',
  })
  const [measurementSystem, setMeasurementSystem] = useState<'imperial' | 'metric' | null>(null)
  const [errors, setErrors] = useState<Record<string, string>>({})
  const [generalError, setGeneralError] = useState('')
  const [submitting, setSubmitting] = useState(false)

  const updateField = (key: keyof RegisterFormState) => (value: string) => {
    setForm(prev => ({ ...prev, [key]: value }))
  }

  const isValid =
    form.email && form.password && form.password === form.passwordConfirm && measurementSystem

  const handleSubmit = async (e: FormEvent) => {
    e.preventDefault()
    if (!isValid) return
    setErrors({})
    setGeneralError('')
    setSubmitting(true)
    try {
      const { user } = await register({
        email: form.email,
        password: form.password,
        password_confirmation: form.passwordConfirm,
        measurement_system: measurementSystem,
        first_name: form.first || undefined,
        last_name: form.last || undefined,
      })
      setUser(user)
      navigate('/workouts')
    } catch (err) {
      const fieldErrors = extractFieldErrors(err)
      if (Object.keys(fieldErrors).length > 0) {
        setErrors(fieldErrors)
      } else {
        setGeneralError(extractMessage(err))
      }
    } finally {
      setSubmitting(false)
    }
  }

  return (
    <AuthLayout
      title="Create your account"
      subtitle="Start tracking every set."
      footer={
        <>
          Already have an account?{' '}
          <Link to="/login" className="font-semibold text-primary">
            Log in
          </Link>
        </>
      }
    >
      <form dusk="register-form" onSubmit={handleSubmit} className="flex flex-col gap-4">
        {generalError && <p className="text-destructive text-sm text-center">{generalError}</p>}
        <div className="grid grid-cols-2 gap-3">
          <Field
            label="First name"
            name="first_name"
            value={form.first}
            onChange={updateField('first')}
            placeholder="Justin"
            error={errors.first_name}
          />
          <Field
            label="Last name"
            name="last_name"
            value={form.last}
            onChange={updateField('last')}
            placeholder="Carter"
            error={errors.last_name}
          />
        </div>

        <Field
          label="Email"
          type="email"
          name="email"
          value={form.email}
          onChange={updateField('email')}
          placeholder="you@example.com"
          error={errors.email}
        />

        <div className="grid grid-cols-2 gap-3">
          <Field
            label="Password"
            type="password"
            name="password"
            value={form.password}
            onChange={updateField('password')}
            placeholder="••••••••"
            error={errors.password}
          />
          <Field
            label="Confirm"
            type="password"
            name="password_confirmation"
            value={form.passwordConfirm}
            onChange={updateField('passwordConfirm')}
            placeholder="••••••••"
          />
        </div>

        <div dusk="measurement-system-picker" className="ps-metric p-3">
          <span className="label-caps text-text-secondary block mb-2">
            Measurement system <span className="text-destructive">*</span>
          </span>
          <SegmentedControl
            value={measurementSystem || ''}
            onChange={v => setMeasurementSystem(v as 'imperial' | 'metric')}
            options={[
              { value: 'imperial', label: 'Imperial (lb, mi)' },
              { value: 'metric', label: 'Metric (kg, km)' },
            ]}
          />
          {errors.measurement_system && (
            <p className="text-destructive text-xs mt-1">{errors.measurement_system}</p>
          )}
          <p className="text-[12px] text-text-muted mt-2">
            Controls unit labels across the app. You can change it later.
          </p>
        </div>

        <Button type="submit" full size="lg" disabled={!isValid || submitting}>
          {submitting ? 'Creating account...' : 'Create Account'}
        </Button>
      </form>
    </AuthLayout>
  )
}

export function PasswordResetRequestPage() {
  const [email, setEmail] = useState('')
  const [sent, setSent] = useState(false)
  const [error, setError] = useState('')
  const [submitting, setSubmitting] = useState(false)

  const handleSubmit = async (e: FormEvent) => {
    e.preventDefault()
    setError('')
    setSubmitting(true)
    try {
      await forgotPassword(email)
      setSent(true)
    } catch (err) {
      setError(extractMessage(err))
    } finally {
      setSubmitting(false)
    }
  }

  return (
    <AuthLayout
      title="Reset password"
      subtitle={sent ? undefined : "We'll email you a reset link."}
      footer={
        <Link to="/login" className="font-semibold text-primary">
          Back to login
        </Link>
      }
    >
      {sent ? (
        <div className="text-center py-2">
          <div
            className="h-12 w-12 rounded-full mx-auto flex items-center justify-center mb-3"
            style={{
              background: 'color-mix(in srgb, var(--color-success) 14%, transparent)',
              color: 'var(--color-success)',
            }}
          >
            <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
              <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z" />
            </svg>
          </div>
          <p className="font-semibold mb-1">Check your inbox</p>
          <p className="text-text-secondary text-sm">
            If an account exists for {email || 'that address'}, a reset link is on its way.
          </p>
        </div>
      ) : (
        <form dusk="forgot-password-form" onSubmit={handleSubmit} className="flex flex-col gap-4">
          {error && <p className="text-destructive text-sm text-center">{error}</p>}
          <Field
            label="Email"
            type="email"
            name="email"
            value={email}
            onChange={setEmail}
            placeholder="you@example.com"
          />
          <Button type="submit" full size="lg" disabled={submitting}>
            {submitting ? 'Sending...' : 'Send Reset Link'}
          </Button>
        </form>
      )}
    </AuthLayout>
  )
}

export function PasswordResetFormPage() {
  const navigate = useNavigate()
  const { token } = useParams<{ token: string }>()
  const [searchParams] = useSearchParams()
  const [email, setEmail] = useState(searchParams.get('email') ?? '')
  const [password, setPassword] = useState('')
  const [passwordConfirm, setPasswordConfirm] = useState('')
  const [done, setDone] = useState(false)
  const [errors, setErrors] = useState<Record<string, string>>({})
  const [generalError, setGeneralError] = useState('')
  const [submitting, setSubmitting] = useState(false)

  const handleSubmit = async (e: FormEvent) => {
    e.preventDefault()
    if (!password || password !== passwordConfirm || !token) return
    setErrors({})
    setGeneralError('')
    setSubmitting(true)
    try {
      await resetPassword({
        email,
        token,
        password,
        password_confirmation: passwordConfirm,
      })
      setDone(true)
    } catch (err) {
      const fieldErrors = extractFieldErrors(err)
      if (Object.keys(fieldErrors).length > 0) {
        setErrors(fieldErrors)
      } else {
        setGeneralError(extractMessage(err))
      }
    } finally {
      setSubmitting(false)
    }
  }

  return (
    <AuthLayout
      title="Set a new password"
      footer={
        <Link to="/login" className="font-semibold text-primary">
          Back to login
        </Link>
      }
    >
      {done ? (
        <div className="text-center py-2">
          <p className="font-semibold mb-1">Password updated</p>
          <p className="text-text-secondary text-sm mb-4">
            You can now log in with your new password.
          </p>
          <Button full onClick={() => navigate('/login')}>
            Go to Login
          </Button>
        </div>
      ) : (
        <form onSubmit={handleSubmit} className="flex flex-col gap-4">
          {generalError && <p className="text-destructive text-sm text-center">{generalError}</p>}
          <Field
            label="Email"
            type="email"
            value={email}
            onChange={setEmail}
            placeholder="you@example.com"
            error={errors.email}
            readOnly={searchParams.has('email')}
          />
          <Field
            label="New password"
            type="password"
            value={password}
            onChange={setPassword}
            placeholder="••••••••"
            error={errors.password}
          />
          <Field
            label="Confirm password"
            type="password"
            value={passwordConfirm}
            onChange={setPasswordConfirm}
            placeholder="••••••••"
          />
          <Button
            type="submit"
            full
            size="lg"
            disabled={!email || !password || password !== passwordConfirm || submitting}
          >
            {submitting ? 'Updating...' : 'Update Password'}
          </Button>
        </form>
      )}
    </AuthLayout>
  )
}
