import { useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { LogOut } from 'lucide-react'
import { PageHeader } from '@/components/ui/page-header'
import { Avatar } from '@/components/ui/avatar'
import { Card } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { SegmentedControl } from '@/components/ui/segmented-control'
import { useApp } from '@/lib/use-app'

function Row({ label, children }: { label: string; children: React.ReactNode }) {
  return (
    <div className="flex items-center justify-between gap-3 py-3 border-b border-border last:border-0">
      <span className="text-sm text-text-secondary shrink-0">{label}</span>
      <div className="min-w-0 flex-1 flex justify-end">{children}</div>
    </div>
  )
}

export function ProfilePage() {
  const navigate = useNavigate()
  const { user, updateUser, logout, toast } = useApp()
  const [first, setFirst] = useState(user.firstName)
  const [last, setLast] = useState(user.lastName)
  const [email, setEmail] = useState(user.email)
  const [password, setPassword] = useState({ current: '', next: '', confirm: '' })

  const isDirty = first !== user.firstName || last !== user.lastName || email !== user.email

  const handleSaveInfo = () => {
    updateUser({ firstName: first, lastName: last, email })
    toast('Profile saved.')
  }

  const handleChangePassword = () => {
    if (!password.current || !password.next || password.next !== password.confirm) return
    setPassword({ current: '', next: '', confirm: '' })
    toast('Password changed.')
  }

  const handleLogout = () => {
    logout()
    navigate('/login')
  }

  const fullName = `${user.firstName} ${user.lastName}`
  const canChangePassword = password.current && password.next && password.next === password.confirm

  return (
    <>
      <PageHeader back onBack={() => navigate('/workouts')} title="Profile" />

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
            <Button size="sm" onClick={handleSaveInfo}>
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
            onChange={v => {
              updateUser({ measurementSystem: v as 'imperial' | 'metric' })
              toast('Measurement system updated.')
            }}
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
            onChange={v => {
              updateUser({ theme: v as 'light' | 'dark' | 'system' })
              toast('Theme updated.')
            }}
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
            disabled={!canChangePassword}
            onClick={handleChangePassword}
          >
            Change Password
          </Button>
        </div>
      </Card>

      <Button variant="secondary" full onClick={handleLogout} icon={<LogOut size={16} />}>
        Log Out
      </Button>
      <div className="h-4" />
    </>
  )
}
