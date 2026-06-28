import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { BrowserRouter, Route, Routes, Navigate, useLocation } from 'react-router-dom'
import { AppProvider, useApp } from '@/lib/store'
import { Shell } from '@/components/shell/shell'
import {
  LoginPage,
  RegisterPage,
  PasswordResetRequestPage,
  PasswordResetFormPage,
} from '@/pages/auth'
import { WorkoutsPage } from '@/pages/workouts'
import { WorkoutDetailPage } from '@/pages/workout-detail'
import { TemplateEditorPage } from '@/pages/template-editor'
import { ExercisesPage } from '@/pages/exercises'
import { ExerciseDetailPage } from '@/pages/exercise-detail'
import { ExerciseProgressPage } from '@/pages/exercise-progress'
import { ProgressPage } from '@/pages/progress'
import { EquipmentPage } from '@/pages/equipment'
import { ProfilePage } from '@/pages/profile'

const queryClient = new QueryClient({
  defaultOptions: {
    queries: { staleTime: 1000 * 60 * 5, gcTime: 1000 * 60 * 10, retry: false },
  },
})

function AuthGate({ children }: { children: React.ReactNode }) {
  const { authed } = useApp()
  const location = useLocation()

  if (!authed) {
    return <Navigate to="/login" replace />
  }

  if (location.pathname === '/login' || location.pathname === '/register') {
    return <Navigate to="/workouts" replace />
  }

  return <>{children}</>
}

export function AppRoot() {
  return (
    <QueryClientProvider client={queryClient}>
      <BrowserRouter>
        <AppProvider>
          <Routes>
            <Route path="/login" element={<LoginPage />} />
            <Route path="/register" element={<RegisterPage />} />
            <Route path="/password/reset" element={<PasswordResetRequestPage />} />
            <Route path="/password/reset/:token" element={<PasswordResetFormPage />} />
            <Route
              path="/*"
              element={
                <AuthGate>
                  <Shell />
                </AuthGate>
              }
            >
              <Route path="workouts" element={<WorkoutsPage />} />
              <Route path="workouts/:id" element={<WorkoutDetailPage />} />
              <Route path="templates/:id" element={<TemplateEditorPage />} />
              <Route path="exercises" element={<ExercisesPage />} />
              <Route path="exercises/:id" element={<ExerciseDetailPage />} />
              <Route path="exercises/:id/progress" element={<ExerciseProgressPage />} />
              <Route path="progress" element={<ProgressPage />} />
              <Route path="equipment" element={<EquipmentPage />} />
              <Route path="profile" element={<ProfilePage />} />
              <Route path="*" element={<Navigate to="/workouts" replace />} />
            </Route>
          </Routes>
        </AppProvider>
      </BrowserRouter>
    </QueryClientProvider>
  )
}
