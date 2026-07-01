# Frontend Test Infrastructure Audit

Status as of 2026-06-30. This document captures the state of Physistrong's React/TypeScript test infrastructure before Phase 2 (PS-88) implementation work lands.

## What Already Exists

### Test Runner Setup

- **Vitest 4.1.9** configured at repo root in `vite.config.ts`:
  - Environment: `jsdom`
  - Globals enabled (`globals: true`)
  - Setup file: `resources/js/test/setup.ts`
  - Include glob: `resources/js/**/*.{test,spec}.{ts,tsx}`
  - Vite plugin conditionally disabled when `process.env.VITEST` is set (prevents interference)
  - Path alias: `@` maps to `./resources/js`

- **Setup file** (`resources/js/test/setup.ts`):
  - Imports `@testing-library/jest-dom`
  - Runs RTL `cleanup()` in `afterEach`
  - Stubs `window.matchMedia` (needed for responsive UI testing)

- **Package.json scripts**:
  - `"test": "vitest run --passWithNoTests"` (CI mode)
  - `"test:watch": "vitest"` (development watch mode)

- **Installed test dependencies**:
  - `vitest@^4.1.9`
  - `@testing-library/react@^16.3.2`
  - `@testing-library/user-event@^14.6.1`
  - `@testing-library/jest-dom@^6.9.1`
  - `jsdom@^29.1.1`

- **TypeScript configuration** (`tsconfig.app.json`):
  - Includes `"types": ["vite/client"]`
  - Does NOT include `vitest/globals` or `@testing-library/jest-dom` types (currently relying on per-file imports)

### Existing Test Coverage

One test file exists:
- **`resources/js/api/progress.test.ts`**: Pure-logic unit test of `transformProgressData` and `extractAllTimeBest` functions across ~18 test cases, no React rendering.

## What's Missing

### HTTP Mocking
- **MSW not installed**. No `mocks/` directory, no server setup, no request handlers.
- No HTTP response fixtures or standardized mock data factory.

### Coverage Configuration
- No `@vitest/coverage-v8` installed.
- No coverage configuration in `vite.config.ts`.
- No coverage thresholds or CI gates.

### Test Utilities
- No shared `renderWithProviders()` helper or similar test utility.
- No QueryClient/provider-aware render wrapper (tests will need to wrap components in providers manually for now).
- No custom matchers or assertion helpers specific to the domain.

### Component and Integration Testing
- Zero tests for React components and pages.
- Zero tests for hooks.
- Zero tests for router integration.
- Zero tests for auth flows (login, logout, token refresh, 401 handling).

## Data-Fetching and Auth Layer

The frontend depends on TanStack Query and a custom auth context for all server communication.

### HTTP Client

- **Location**: `resources/js/api/client.ts`
- **Configuration**:
  - Axios instance with `baseURL: '/api/v1'`
  - Request interceptor attaches `Authorization: Bearer <localStorage['ps_token']>`
  - Response interceptor on 401 with valid token: clears token, hard-redirects to `/login`
  - No CSRF handling (pure JWT bearer tokens via Laravel Passport, not Sanctum cookies)

### Query Setup

- **Location**: `resources/js/app-root.tsx`
- **QueryClient defaults**:
  - `staleTime: 5min`
  - `gcTime: 10min` (formerly `cacheTime`)
  - `retry: false`
- **Version**: TanStack Query v5

### Auth Context

- **AuthContext**: `resources/js/lib/auth-context.ts`
- **AuthProvider** (`resources/js/lib/auth-provider.tsx`):
  - Hydrates user on mount via `GET /user` if a token exists in localStorage
  - Exposes `user` and `logout` to descendants
- **useAuth hook**: `resources/js/hooks/use-auth.ts`

### Router

- **Library**: react-router-dom v7
- **Setup**: `BrowserRouter` in `app-root.tsx`
- **Public routes**: `/login`, `/register`, `/password/reset(/:token)`
- **Authenticated routes** (behind `<Shell/>` + `AuthGate`):
  - `/workouts`, `/workouts/:id`
  - `/templates`, `/templates/:id`
  - `/exercises`, `/exercises/:id`, `/exercises/:id/progress`
  - `/progress`
  - `/equipment`
  - `/profile`

## API Endpoint Inventory

All endpoints under `/api/v1`. Responses follow REST conventions with snake_case fields and numeric IDs. See response envelope rules at the end of this section.

### Authentication

| Method | Endpoint | Response | Notes |
|--------|----------|----------|-------|
| POST | `/register` | 201 `{user, token}` | Unwrapped (not `{data: ...}`) |
| POST | `/login` | 200 `{user, token}` | Unwrapped |
| POST | `/logout` | 204 | No body |
| POST | `/password/forgot` | — | See password reset flow |
| POST | `/password/reset` | — | See password reset flow |

### User

| Method | Endpoint | Response | Notes |
|--------|----------|----------|-------|
| GET | `/user` | 200 `{data: UserResource}` | Hydrates auth context on app load |
| PUT | `/user` | 200 `{data: UserResource}` | Scoped to authenticated user |

### Equipment Types

| Method | Endpoint | Response | Notes |
|--------|----------|----------|-------|
| GET | `/equipment-types` | 200 `{data: [...]}` | No pagination; includes system-seeded rows (user_id: null) and user rows |
| POST | `/equipment-types` | 201 `{data: EquipmentTypeResource}` | Create custom equipment type |
| GET | `/equipment-types/{id}` | 200 `{data: EquipmentTypeResource}` | |
| PUT | `/equipment-types/{id}` | 200 `{data: EquipmentTypeResource}` | |
| DELETE | `/equipment-types/{id}` | 204 or 409 | 409 `{message: "..."}` if referenced by exercises |

### Exercises

| Method | Endpoint | Response | Notes |
|--------|----------|----------|-------|
| GET | `/exercises` | 200 `{data: [...]}` | No pagination; includes system rows and user rows; resolved equipment_type FK |
| POST | `/exercises` | 201 `{data: ExerciseResource}` | Create exercise; type determines required `type_attributes` structure |
| GET | `/exercises/{id}` | 200 `{data: ExerciseResource}` | |
| PUT | `/exercises/{id}` | 200 `{data: ExerciseResource}` | 403 if not owner |
| DELETE | `/exercises/{id}` | 204 or 409 | 409 if referenced by workouts/templates |
| GET | `/exercises/{id}/progress?range=1m\|3m\|6m\|1y\|all` | 200 `{data: ...}` | Custom shape (not ExerciseResource); data-fetched by `ExerciseProgressService` |
| GET | `/exercises/{id}/records` | 200 `{data: ...}` | Custom shape (not ExerciseResource); returns PR/records only |

Exercise types use Class Table Inheritance: base `exercises` table + child tables for `resistance`, `timed_hold`, `distance`, `interval`. Type attribute structure is application-layer config (frontend and backend must agree).

### Workouts

| Method | Endpoint | Response | Notes |
|--------|----------|----------|-------|
| GET | `/workouts` | 200 `{data: [...], links: {...}, meta: {...}}` | **ONLY paginated endpoint** (`->paginate(15)`); infinite query candidate |
| POST | `/workouts` | 201 `{data: WorkoutResource}` | Create workout session |
| GET | `/workouts/{id}` | 200 `{data: WorkoutResource}` | Includes nested entries, groups, and metrics |
| PUT | `/workouts/{id}` | 200 `{data: WorkoutResource}` | |
| DELETE | `/workouts/{id}` | 204 | Cascades to entries and their metrics |
| POST | `/workouts/{id}/exercises` | 201 `{data: ...}` | Attach exercise to workout group |
| DELETE | `/workouts/{id}/exercises` | 204 | Detach exercise |
| PUT | `/workouts/{id}/exercises/reorder` | 200 | Reorder exercises within group |
| GET | `/workouts/{id}/entries` | 200 `{data: [...]}` | Scoped resource |
| POST | `/workouts/{id}/entries` | 201 `{data: WorkoutEntryResource}` | Create entry (e.g., set/rep log) |
| GET | `/workouts/{id}/entries/{entry_id}` | 200 `{data: WorkoutEntryResource}` | |
| PUT | `/workouts/{id}/entries/{entry_id}` | 200 `{data: WorkoutEntryResource}` | |
| DELETE | `/workouts/{id}/entries/{entry_id}` | 204 | |
| PUT | `/workouts/{id}/entries/reorder` | 200 | Reorder entries within workout |
| POST | `/workouts/{id}/copy` | 201 `{data: WorkoutResource}` | Clone workout; returns new `WorkoutResource` |
| POST | `/workouts/{id}/groups` | 201 `{data: ...}` | Create group (e.g., superset) |
| DELETE | `/workouts/{id}/groups/{group_id}` | 204 | |
| PUT | `/workouts/{id}/groups/{group_id}` | 200 | Update group |
| POST | `/workouts/{id}/groups/{group_id}/entries` | 201 `{data: WorkoutEntryResource}` | Create entry in specific group |
| DELETE | `/workouts/{id}/groups/{group_id}/entries/{entry_id}` | 204 | Detach entry from group |

Workouts support ungrouped and grouped (superset/compound) layouts. Composable metrics attach to entries (8 metric tables, nullable target/actual, type-driven expectations).

### Templates

| Method | Endpoint | Response | Notes |
|--------|----------|----------|-------|
| GET | `/templates` | 200 `{data: [...]}` | No pagination |
| POST | `/templates` | 201 `{data: TemplateResource}` | Create template |
| GET | `/templates/{id}` | 200 `{data: TemplateResource}` | Includes nested exercises and groups |
| PUT | `/templates/{id}` | 200 `{data: TemplateResource}` | |
| DELETE | `/templates/{id}` | 204 | |
| POST | `/templates/{id}/exercises` | 201 `{data: ...}` | Attach exercise to template |
| DELETE | `/templates/{id}/exercises` | 204 | Detach exercise |
| PUT | `/templates/{id}/exercises/reorder` | 200 | Reorder exercises |
| POST | `/templates/{id}/clone` | 201 `{data: WorkoutResource}` | Clone template to new workout (returns `WorkoutResource`) |
| POST | `/templates/{id}/groups` | 201 `{data: ...}` | Create group |
| DELETE | `/templates/{id}/groups/{group_id}` | 204 | |
| PUT | `/templates/{id}/groups/{group_id}` | 200 | Update group |
| POST | `/templates/{id}/groups/{group_id}/exercises` | 201 `{data: ...}` | Add exercise to group |
| DELETE | `/templates/{id}/groups/{group_id}/exercises/{exercise_id}` | 204 | |

### Response Envelope Rules

- **Standard resource responses**: Wrapped in `{data: ...}` (single or array).
- **Paginated responses** (GET `/workouts` only): `{data: [...], links: {...}, meta: {...}}`.
- **Auth responses** (register, login): **NOT wrapped** — top-level `{user, token}`.
- **Field encoding**:
  - All fields: snake_case
  - IDs: numeric integers
  - Metrics (weight, distance, etc.): decimal strings (e.g., `"100.00"`)
  - Reps/counts: integers
- **Success responses**:
  - 200: Full resource(s)
  - 201: Created resource
  - 204: No content (destroys, detaches, logout)
- **Error responses**:
  - 401: Token expired/invalid (interceptor redirects to `/login`)
  - 403: Not authorized (e.g., editing someone else's exercise)
  - 404: Resource not found
  - 409: Conflict `{message: "..."}` (e.g., deleting in-use equipment)
  - 422: Validation error `{message: "...", errors: {field: [...]}}`

## Component and Hook Inventory

### Pages (route-level components)

| Component | Location | Features | Testing Priority |
|-----------|----------|----------|-------------------|
| `WorkoutsPage` | `pages/workouts.tsx` | Infinite list via `useInfiniteQuery(['workouts'])`, session CRUD | High (core feature, pagination) |
| `WorkoutDetailPage` | `pages/workout-detail.tsx` | Edit grouped/ungrouped entries, PR badges, ~12 mutations, dnd-kit reorder | Very High (most complex) |
| `TemplateEditorPage` | `pages/template-editor.tsx` | Create/edit templates, drag-and-drop reorder, groups | High (composable structure) |
| `ExercisesPage` | `pages/exercises.tsx` | CRUD, type-specific `type_attributes` forms, equipment linking | Medium (CRUD + nested schema) |
| `ExerciseDetailPage` | `pages/exercise-detail.tsx` | Show exercise with metadata and linked workouts | Medium |
| `ExerciseProgressPage` | `pages/exercise-progress.tsx` | Recharts visualization, range selector (`1m`, `3m`, `6m`, `1y`, `all`) | Medium (data transform + chart) |
| `TemplatesPage` | `pages/templates.tsx` | List templates, CRUD, clone-to-workout | Medium |
| `EquipmentPage` | `pages/equipment.tsx` | CRUD gym equipment types, 409 handling on in-use deletion | Low-Medium |
| `ProfilePage` | `pages/profile.tsx` | User settings (measurement system, etc.), PUT `/user` | Low (simple forms) |
| `LoginPage` / `RegisterPage` | `pages/auth.tsx` | Registration, login, forgot-password, reset-password flows | Very High (auth critical path) |

### Hooks

| Hook | Location | Responsibilities | Testing Priority |
|------|----------|------------------|-------------------|
| `useAuth` | `hooks/use-auth.ts` | Get current user and logout function from `AuthContext` | High (security boundary) |
| `useExerciseProgress` | `hooks/use-exercise-progress.ts` | Query exercise progress data with range parameter, handle transforms | High (data transform) |
| (TanStack Query hooks) | Throughout | `useQuery`, `useInfiniteQuery`, `useMutation` for API calls | High (data fetching) |

### Utility Functions (Unit-Test Candidates)

| Module | Functions | Testing Priority |
|--------|-----------|------------------|
| `resources/js/lib/formatters.ts` | `formatDate()`, `formatDuration()` | Medium (format edge cases) |
| `resources/js/lib/units.ts` | `unitLabel()` — mirrors FE measurement-label resolution (kg/lb, km/mi, km·h⁻¹/mph) | Medium (unit system parity with backend) |
| `resources/js/lib/domain.ts` | `workoutCompletion()`, `equipmentName()`, `bestWeight()` — domain calculations | Medium (business logic) |
| `resources/js/api/transformers.ts` | Snake-to-camel mappers (`RawWorkout` → domain types, etc.) | High (field-name bugs silent in typed code) |
| `resources/js/api/progress.ts` | `transformProgressData()`, `extractAllTimeBest()` | High (only existing test file; expands here) |

---

**Integration test candidates**: Components that compose multiple hooks, TanStack Query providers, and API calls. Start with `WorkoutDetailPage` and auth pages.

**Unit test candidates**: Transformers, formatters, and domain functions that have edge cases or cross-language parity (e.g., `unitLabel` must match backend `MeasurementLabelService`).
