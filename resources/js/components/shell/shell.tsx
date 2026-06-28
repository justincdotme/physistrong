import { useLocation, useNavigate, Outlet } from 'react-router-dom'
import { Dumbbell, List, TrendingUp, LayoutGrid } from 'lucide-react'
import { useApp } from '@/lib/use-app'
import { Avatar } from '@/components/ui/avatar'

interface NavItem {
  key: string
  label: string
  icon: React.ComponentType<{ size: number; strokeWidth: number }>
  to: string
}

const NAV_ITEMS: NavItem[] = [
  { key: 'workouts', label: 'Workouts', icon: Dumbbell, to: '/workouts' },
  { key: 'exercises', label: 'Exercises', icon: List, to: '/exercises' },
  { key: 'progress', label: 'Progress', icon: TrendingUp, to: '/progress' },
  { key: 'equipment', label: 'Equipment', icon: LayoutGrid, to: '/equipment' },
]

function getActiveKey(pathname: string): string {
  for (const item of NAV_ITEMS) {
    if (pathname === item.to || pathname.startsWith(item.to + '/')) {
      return item.key
    }
  }
  return 'workouts'
}

export function Shell() {
  const location = useLocation()
  const navigate = useNavigate()
  const { user } = useApp()
  const activeKey = getActiveKey(location.pathname)
  const fullName = `${user.firstName} ${user.lastName}`

  return (
    <div className="min-h-[100dvh] md:flex">
      {/* Desktop side nav */}
      <aside className="hidden md:flex md:flex-col md:w-[244px] md:shrink-0 md:h-[100dvh] md:sticky md:top-0 border-r border-border bg-surface-card px-4 py-6">
        <button
          onClick={() => navigate('/workouts')}
          className="flex items-center gap-2.5 px-2 mb-8 hover:opacity-75 transition-opacity"
        >
          <span
            className="h-9 w-9 rounded-xl flex items-center justify-center text-white"
            style={{ backgroundImage: 'var(--gradient-primary)' }}
          >
            <Dumbbell size={20} strokeWidth={2.5} />
          </span>
          <span className="font-extrabold text-lg tracking-tight">Physistrong</span>
        </button>

        <nav className="flex flex-col gap-1">
          {NAV_ITEMS.map(item => {
            const isActive = activeKey === item.key
            const Icon = item.icon
            return (
              <button
                key={item.key}
                onClick={() => navigate(item.to)}
                className="flex items-center gap-3 px-3 h-12 rounded-lg font-semibold text-sm transition-colors text-left"
                style={
                  isActive
                    ? {
                        color: 'var(--color-primary)',
                        background: 'color-mix(in srgb, var(--color-primary) 12%, transparent)',
                      }
                    : { color: 'var(--color-text-secondary)' }
                }
              >
                <Icon size={22} strokeWidth={2} />
                <span>{item.label}</span>
              </button>
            )
          })}
        </nav>

        <button
          onClick={() => navigate('/profile')}
          className="mt-auto flex items-center gap-3 px-2 py-2 rounded-lg hover:bg-surface-muted text-left transition-colors"
        >
          <Avatar name={fullName} size={38} />
          <span className="min-w-0">
            <span className="block font-semibold text-sm truncate">{fullName}</span>
            <span className="block text-[12px] text-text-secondary truncate">View profile</span>
          </span>
        </button>
      </aside>

      {/* Main column */}
      <div className="flex-1 min-w-0 flex flex-col">
        {/* Mobile top bar */}
        <header
          className="md:hidden sticky top-0 z-30 bg-surface-bg/90 backdrop-blur border-b border-border"
          style={{ paddingTop: 'env(safe-area-inset-top, 0px)' }}
        >
          <div className="flex items-center justify-between px-4 h-14">
            <button
              onClick={() => navigate('/workouts')}
              className="flex items-center gap-2 hover:opacity-75 transition-opacity"
            >
              <span
                className="h-8 w-8 rounded-lg flex items-center justify-center text-white"
                style={{ backgroundImage: 'var(--gradient-primary)' }}
              >
                <Dumbbell size={18} strokeWidth={2.5} />
              </span>
              <span className="font-extrabold text-[17px] tracking-tight">Physistrong</span>
            </button>
            <button
              onClick={() => navigate('/profile')}
              className="flex items-center gap-2 hover:opacity-75 transition-opacity"
              aria-label="Profile"
            >
              <span className="text-sm font-semibold text-text-secondary">{user.firstName}</span>
              <Avatar name={fullName} size={34} />
            </button>
          </div>
        </header>

        <main
          id="ps-main"
          className="flex-1 px-4 md:px-8 pt-5 pb-28 md:pb-10 mx-auto w-full max-w-[760px]"
        >
          <Outlet />
        </main>
      </div>

      {/* Mobile bottom tab bar */}
      <nav
        className="md:hidden fixed bottom-0 inset-x-0 z-40 bg-surface-card border-t border-border"
        style={{
          paddingBottom: 'env(safe-area-inset-bottom, 0px)',
          boxShadow: '0 -4px 16px rgba(0,0,0,0.07)',
        }}
      >
        <div className="flex">
          {NAV_ITEMS.map(item => {
            const isActive = activeKey === item.key
            const Icon = item.icon
            return (
              <button
                key={item.key}
                onClick={() => navigate(item.to)}
                className="flex-1 flex flex-col items-center justify-center gap-0.5 h-[60px] min-h-[44px] transition-colors"
                style={{
                  color: isActive ? 'var(--color-primary)' : 'var(--color-text-muted)',
                }}
              >
                <Icon size={23} strokeWidth={isActive ? 2.4 : 2} />
                <span className="text-[11px] font-semibold">{item.label}</span>
              </button>
            )
          })}
        </div>
      </nav>
    </div>
  )
}
