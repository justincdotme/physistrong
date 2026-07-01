# Frontend Testing Guide

Physistrong's React/TypeScript test infrastructure uses Vitest with MSW (Mock Service Worker) for request mocking, real-captured fixtures for test data, and TanStack React Query with custom providers for integration testing.

## Running Tests

Test scripts live in `package.json`:

```bash
npm run test              # CI mode: vitest run with --passWithNoTests
npm run test:watch       # Watch mode: auto-rerun on changes
npm run test:coverage    # Generate coverage report (v8, text + lcov)
```

Tests run against `jsdom` environment. Pre-commit hooks run the full test suite inside the app container where both PHP and Node toolchains are available (see `bin/test` and `.githooks/pre-commit`).

### Test File Organization

- **Co-located tests:** Test files live next to their source: `resources/js/pages/foo.test.tsx` next to `foo.tsx`, `resources/js/lib/*.test.ts`, `resources/js/api/*.test.ts`
- **Shared test infra:** `resources/js/test/`
  - `setup.ts` — Vitest lifecycle (MSW server, RTL cleanup, matchMedia stub)
  - `server.ts` — setupServer export
  - `render.tsx` — `renderWithProviders` helper and re-exports from testing-library
  - `mocks/handlers/` — HTTP endpoint mocks, one module per resource
  - `mocks/fixtures/` — Real captured API responses, organized by resource

## MSW Handler and Fixture Pattern

Fixtures are REAL captured HTTP responses from a live Physistrong instance, sanitized for test use (tokens replaced, PII scrubbed, timestamps pinned). Handlers serve them via Mock Service Worker v2, ensuring unmocked requests fail loudly — a regression net, not just convenience.

### Handler Organization

Handlers live in `resources/js/test/mocks/handlers/`, one module per resource (e.g., `exercises.ts`, `workouts.ts`). Each module exports an array of `http.*` handlers, aggregated in `index.ts` and passed to `setupServer(...)`:

```typescript
// resources/js/test/mocks/handlers/exercises.ts
import { http, HttpResponse } from 'msw'
import exerciseList from '../fixtures/exercises/list.json'
import createdResistance from '../fixtures/exercises/created-resistance.json'

const FORBIDDEN_ID = '9001'
const IN_USE_ID = '9002'

export const exerciseHandlers = [
  http.get('/api/v1/exercises', () => HttpResponse.json(exerciseList)),

  http.get('/api/v1/exercises/:id', ({ params }) => {
    if (params.id === FORBIDDEN_ID) {
      return HttpResponse.json({ message: 'Forbidden' }, { status: 403 })
    }
    return HttpResponse.json(exerciseResistance)
  }),

  http.delete('/api/v1/exercises/:id', ({ params }) => {
    if (params.id === IN_USE_ID) {
      return HttpResponse.json({ message: 'Exercise in use' }, { status: 409 })
    }
    return new HttpResponse(null, { status: 204 })
  }),
]
```

### Fixtures and Sentinel IDs

Fixtures are plain JSON files organized under `resources/js/test/mocks/fixtures/<resource>/<scenario>.json`. Each is a real API response (single resource wrapped in `{data: ...}`, arrays wrapped in `{data: [...]}`, paginated responses with `{data, links, meta}`). Exceptions: auth endpoints (`/login`, `/register`) return unwrapped `{user, token}`.

Fixtures reflect reality. Do not hand-edit them to "fix" a shape mismatch; if a fixture looks wrong, re-capture it. The entire test value depends on fixtures matching the real API contract.

Error paths are triggered via **sentinel IDs** documented in handler comments:
- Exercise/workout ID `'9001'` → 403 (forbidden, not owner)
- Exercise/workout ID `'9002'` → 409 (conflict, in use)
- Email `'invalid@sentinel.test'` during login → 422 (invalid credentials)

This reduces boilerplate `server.use()` overrides. When a handler must be truly overridden per-test, `server.use()` still works:

```typescript
it('shows error when create fails', async () => {
  server.use(
    http.post('/api/v1/exercises', () =>
      HttpResponse.json({ message: 'Server error' }, { status: 500 })
    )
  )
  // test code
})
```

### Response Envelope Rules

When authoring new handlers, follow these envelope conventions:

| Response Type | Envelope | Example |
|---------------|----------|---------|
| Single resource | `{data: {...}}` | `{data: {id: 1, name: "Squat"}}` |
| Resource array | `{data: [...]}` | `{data: [{id: 1, ...}, {id: 2, ...}]}` |
| Paginated list (GET `/workouts` only) | `{data: [...], links: {...}, meta: {...}}` | Per Laravel pagination format |
| Auth responses (register, login) | **Unwrapped** | `{user: {...}, token: "..."}` |
| Error: validation | `{message: "...", errors: {field: [...]}}` | 422 status |
| Error: other | `{message: "..."}` | 403, 404, 409 status |

Helper function `errorEnvelope(status, message, errors?)` from `_helpers.ts` simplifies error responses:

```typescript
import { errorEnvelope } from './_helpers'

return errorEnvelope(422, 'Validation failed', { name: ['Required'] })
```

## `renderWithProviders` Usage

Helper function `renderWithProviders` wraps components in a provider stack (QueryClient, MemoryRouter, AuthContext, AppProvider) and re-exports testing-library queries.

Import: `import { renderWithProviders, screen, userEvent, waitFor } from '@/test/render'`

Basic usage:
```typescript
renderWithProviders(<ExercisesPage />)
await screen.findByText('Squat')
```

Options object `{user, route, path}`:

- **`user`** (default: `testUser`): Pass a custom user object or `null` for unauthenticated/guest pages.
  ```typescript
  renderWithProviders(<LoginPage />, { user: null })
  ```

- **`route`** (default: `'/'`): Initial URL in the MemoryRouter.
  ```typescript
  renderWithProviders(<App />, { route: '/workouts' })
  ```

- **`path`** (required for components reading `useParams()`): Route pattern plus matching `route`. Router wraps the component in `<Routes><Route path={path} element={...} /></Routes>`.
  ```typescript
  // Component reads useParams().id, so:
  renderWithProviders(<WorkoutDetail />, {
    path: '/workouts/:id',
    route: '/workouts/42',
  })
  ```

Test user defaults:
```typescript
{
  id: '1',
  firstName: 'Test',
  lastName: 'User',
  email: 'test@example.com',
  measurementSystem: 'imperial',
  theme: 'light',
}
```

## Regenerating Fixtures

Fixtures are captured from a live Physistrong instance and must stay in sync with the real API contract. To regenerate or add fixtures:

1. Ensure a live Physistrong instance is running (e.g., `https://physistrong.justinc.srv`) and accessible.
2. Authenticate with a real test account.
3. Run the capture script:
   ```bash
   node temp/capture.mjs
   ```
   This script:
   - Creates throwaway records via the real API (exercises, equipment types, workouts, templates).
   - Records API responses into `temp/captured/`.
   - Cleans up created records.
   - Applies sanitization rules (see below).
   - Stages sanitized responses to `resources/js/test/mocks/fixtures/`.

4. Review staged fixtures for correctness (curl or browser against the live API if unsure).
5. Do not hand-edit fixtures. If a captured response looks wrong, investigate the live API, fix the root cause, and re-run the script.

### Fixture Sanitization Rules

The capture script applies these transformations before writing fixtures:

- **Tokens:** `"test-access-token"` (placeholder, never a real token)
- **Timestamps:** Pinned to `"2026-01-01T00:00:00.000000Z"` (fixed reference, time-independent tests)
- **User IDs:** Consistent test user ID (e.g., `3` across all fixtures)
- **PII:** Generic test values (e.g., email `"test@example.com"`, names `"Claude"` / `"Ai"`)
- **Fixture-generated names:** Prefixed with `"Capture Test "` + timestamp (e.g., `"Capture Test Resistance 1782864880612"`) to avoid collisions during re-capture

These rules ensure fixtures are deterministic, anonymous, and valid indefinitely.

## Testing Philosophy

Integration tests via MSW-backed page tests are the default. Unit tests are reserved for genuinely branchy pure logic.

### Integration Tests (Page/Component Tests)

- Default pattern: Render a full page or complex component against the MSW server.
- Render with `renderWithProviders`, interact with `userEvent`, await results with `screen.findBy*` or `waitFor()`.
- Assert user-visible behavior via accessible queries (`getByRole`, `getByLabelText`, `getByText`), never test-id attributes or implementation details.
- Example:
  ```typescript
  it('displays exercises from the fixture with correct count', async () => {
    renderWithProviders(<ExercisesPage />)
    await screen.findByText('Squat')
    expect(screen.getByText('5 in your catalog')).toBeInTheDocument()
  })
  ```

### Unit Tests (Pure Logic)

Earn their place by testing logic with multiple branches or defaults that can silently drift. Examples:

- Formatters and transformers with edge cases (date formatting, unit conversion, snake-to-camel mapping).
- Domain helpers with branchy logic (workout completion status, weight calculations).
- Pure utility functions (validation, filtering, sorting).

No MSW server, no data fetching. Test inputs and outputs directly via `expect()`.

### What Not to Test

- Tautological tests (input X, output X).
- Setter pass-throughs (testing that property assignment works).
- Language/framework guarantees (React prop types, TypeScript type checking, HTTP status codes enforced by the language).
- Implementation details (component internals, re-render counts, test-id attributes).

If you'd falsely break a test by renaming a variable or refactoring an internal helper without changing behavior, the test is testing implementation, not behavior — delete or rewrite it.
