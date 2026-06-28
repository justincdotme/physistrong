import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { BrowserRouter, Route, Routes, Navigate } from 'react-router-dom'
import { AppProvider } from '@/lib/store'
import { AuthProvider } from '@/lib/auth-provider'
import { useAuth } from '@/hooks/use-auth'
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
  const { user, isLoading } = useAuth()
  if (isLoading) return null
  if (!user) return <Navigate to="/login" replace />
  return <>{children}</>
}

function GuestGate({ children }: { children: React.ReactNode }) {
  const { user, isLoading } = useAuth()
  if (isLoading) return null
  if (user) return <Navigate to="/workouts" replace />
  return <>{children}</>
}

export function AppRoot() {
  return (
    <QueryClientProvider client={queryClient}>
      <BrowserRouter>
        <AuthProvider>
          <AppProvider>
            <Routes>
              <Route
                path="/login"
                element={
                  <GuestGate>
                    <LoginPage />
                  </GuestGate>
                }
              />
              <Route
                path="/register"
                element={
                  <GuestGate>
                    <RegisterPage />
                  </GuestGate>
                }
              />
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
        </AuthProvider>
      </BrowserRouter>
    </QueryClientProvider>
  )
}
