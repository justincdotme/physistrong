import type { ReactNode } from 'react'
import { useState } from 'react'
import { isAxiosError } from 'axios'
import { useMutation } from '@tanstack/react-query'
import { LogOut } from 'lucide-react'
import { extractFieldErrors } from '@/api/errors'
import { PageHeader } from '@/components/ui/page-header'
import { Avatar } from '@/components/ui/avatar'
import { Card } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { SegmentedControl } from '@/components/ui/segmented-control'
import { useAuth } from '@/hooks/use-auth'
import { useApp } from '@/lib/use-app'
import { updateProfile } from '@/api/user'

function applyTheme(theme: string) {
  let resolved = theme
  if (theme === 'system') {
    resolved = window.matchMedia?.('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'
  }
  document.documentElement.classList.toggle('dark', resolved === 'dark')
}

function Row({ label, children }: { label: string; children: ReactNode }) {
  return (
    <div className="flex items-center justify-between gap-3 py-3 border-b border-border last:border-0">
      <span className="text-sm text-text-secondary shrink-0">{label}</span>
      <div className="min-w-0 flex-1 flex justify-end">{children}</div>
    </div>
  )
}

export function ProfilePage() {
  const { user, setUser, handleLogout } = useAuth()
  const { toast } = useApp()
  const [first, setFirst] = useState(user?.firstName ?? '')
  const [last, setLast] = useState(user?.lastName ?? '')
  const [email, setEmail] = useState(user?.email ?? '')
  const [password, setPassword] = useState({ current: '', next: '', confirm: '' })
  const [passwordError, setPasswordError] = useState('')

  const infoMutation = useMutation({
    mutationFn: updateProfile,
    onSuccess: updated => {
      setUser(updated)
      toast('Profile saved.')
    },
    onError: (err: unknown) => {
      const fieldErrors = extractFieldErrors(err)
      const msg = Object.values(fieldErrors)[0]
      if (msg) {
        toast(msg, 'error')
      } else if (isAxiosError(err) && err.response?.status === 422) {
        toast('Could not save.', 'error')
      } else {
        toast('Could not save. Try again.', 'error')
      }
    },
  })

  const passwordMutation = useMutation({
    mutationFn: updateProfile,
    onSuccess: () => {
      setPassword({ current: '', next: '', confirm: '' })
      toast('Password changed.')
    },
    onError: (err: unknown) => {
      if (isAxiosError(err) && err.response?.status === 422) {
        const m = extractFieldErrors(err)
        setPasswordError(m.current_password ?? m.password ?? 'Could not change password.')
      } else {
        setPasswordError('Could not change password. Try again.')
      }
    },
  })

  const preferenceMutation = useMutation({
    mutationFn: updateProfile,
    onSuccess: (updated, variables) => {
      setUser(updated)
      toast(variables.theme !== undefined ? 'Theme updated.' : 'Measurement system updated.')
    },
    onError: () => {
      toast('Could not save preference. Try again.', 'error')
    },
  })

  if (!user) return null

  const isDirty = first !== user.firstName || last !== user.lastName || email !== user.email

  const handleSaveInfo = () => {
    infoMutation.mutate({ first_name: first, last_name: last, email })
  }

  const handleChangePassword = () => {
    if (!password.current || !password.next || password.next !== password.confirm) return
    setPasswordError('')
    passwordMutation.mutate({
      current_password: password.current,
      password: password.next,
      password_confirmation: password.confirm,
    })
  }

  const handlePreferenceChange = (field: string, value: string) => {
    if (field === 'theme') applyTheme(value)
    preferenceMutation.mutate({ [field]: value })
  }

  const fullName = `${user.firstName} ${user.lastName}`
  const canChangePassword = password.current && password.next && password.next === password.confirm

  return (
    <div dusk="profile-page">
      <PageHeader title="Profile" />

      <div className="flex items-center gap-3 mb-6">
        <Avatar name={fullName} size={56} />
        <div className="min-w-0">
          <div className="font-bold text-lg truncate">{fullName}</div>
          <div className="text-sm text-text-secondary truncate">{user.email}</div>
        </div>
      </div>

      <h2 className="label-caps text-text-secondary mb-2">Account</h2>
      <Card className="px-4 mb-6">
        <Row label="First name">
          <input
            value={first}
            onChange={e => setFirst(e.target.value)}
            className="ps-input px-2.5 py-1.5 text-sm text-right max-w-[180px]"
          />
        </Row>
        <Row label="Last name">
          <input
            value={last}
            onChange={e => setLast(e.target.value)}
            className="ps-input px-2.5 py-1.5 text-sm text-right max-w-[180px]"
          />
        </Row>
        <Row label="Email">
          <input
            value={email}
            onChange={e => setEmail(e.target.value)}
            className="ps-input px-2.5 py-1.5 text-sm text-right max-w-[200px]"
          />
        </Row>
        {isDirty && (
          <div className="py-3">
            <Button size="sm" onClick={handleSaveInfo} disabled={infoMutation.isPending}>
              Save Changes
            </Button>
          </div>
        )}
      </Card>

      <h2 className="label-caps text-text-secondary mb-2">Preferences</h2>
      <Card className="p-4 mb-6 flex flex-col gap-4">
        <div>
          <div className="text-sm font-medium mb-2">Measurement system</div>
          <SegmentedControl
            value={user.measurementSystem}
            onChange={v => handlePreferenceChange('measurement_system', v)}
            options={[
              { value: 'imperial', label: 'Imperial (lb, mi)' },
              { value: 'metric', label: 'Metric (kg, km)' },
            ]}
          />
        </div>
        <div>
          <div className="text-sm font-medium mb-2">Theme</div>
          <SegmentedControl
            value={user.theme}
            onChange={v => handlePreferenceChange('theme', v)}
            options={[
              { value: 'light', label: 'Light' },
              { value: 'dark', label: 'Dark' },
              { value: 'system', label: 'System' },
            ]}
          />
        </div>
      </Card>

      <h2 className="label-caps text-text-secondary mb-2">Security</h2>
      <Card className="px-4 mb-6">
        {passwordError && <p className="text-destructive text-xs pt-3">{passwordError}</p>}
        <Row label="Current">
          <input
            type="password"
            value={password.current}
            onChange={e => setPassword({ ...password, current: e.target.value })}
            placeholder="••••••••"
            className="ps-input px-2.5 py-1.5 text-sm text-right max-w-[180px]"
          />
        </Row>
        <Row label="New">
          <input
            type="password"
            value={password.next}
            onChange={e => setPassword({ ...password, next: e.target.value })}
            placeholder="••••••••"
            className="ps-input px-2.5 py-1.5 text-sm text-right max-w-[180px]"
          />
        </Row>
        <Row label="Confirm">
          <input
            type="password"
            value={password.confirm}
            onChange={e => setPassword({ ...password, confirm: e.target.value })}
            placeholder="••••••••"
            className="ps-input px-2.5 py-1.5 text-sm text-right max-w-[180px]"
          />
        </Row>
        <div className="py-3">
          <Button
            size="sm"
            variant="secondary"
            disabled={!canChangePassword || passwordMutation.isPending}
            onClick={handleChangePassword}
          >
            Change Password
          </Button>
        </div>
      </Card>

      <Button
        dusk="logout-btn"
        variant="secondary"
        full
        onClick={handleLogout}
        icon={<LogOut size={16} />}
      >
        Log Out
      </Button>
      <div className="h-4" />
    </div>
  )
}
