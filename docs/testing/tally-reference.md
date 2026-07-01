# Tally Frontend Testing Architecture Reference

This document captures Tally's frontend testing setup (`/home/justin/code/scifeks/tally/ui`) as a reference for designing Physistrong's testing patterns. Tally's approach emphasizes integration tests over unit tests, mock server handlers as the primary seam for error testing, and fixture-based test data.

## UI Root and Test Layout

Tests live in a top-level `tests/` directory, parallel to `src/`. This separation keeps test infrastructure separate from source and makes build configuration simpler.

```
tests/
├── fixtures/
├── handlers/
├── integration/
├── setup.ts
└── unit/
src/
```

## Vitest Configuration

Vitest is configured in the `test` block of `vite.config.ts` (no separate `vitest.config.ts` file):

```typescript
test: {
  environment: 'jsdom',
  globals: true,
  passWithNoTests: true,
  setupFiles: ['./tests/setup.ts'],
  include: ['tests/**/*.test.{ts,tsx}'],
  coverage: {
    provider: 'v8',
    reporter: ['text', 'lcov'],
    exclude: ['tests/**', '**/*.d.ts', '**/*.config.*'],
  },
},
```

Key settings:
- `jsdom` environment simulates a browser DOM.
- `globals: true` makes `describe`, `it`, `expect` available without imports and auto-cleans up after each test.
- `passWithNoTests: true` allows test suites to pass even when no tests exist (useful during development).
- `setupFiles` runs `tests/setup.ts` before the test suite.

Path alias `@` maps to `./src` via `vite.config.ts` resolve configuration. `tsconfig.app.json` adds type support with `"types": ["vitest/globals", "@testing-library/jest-dom"]`.

## Setup File

`tests/setup.ts` runs once before all tests and handles:

1. **Side-effect imports:** `import '@testing-library/jest-dom'` enables matchers like `toBeInTheDocument()`.

2. **jsdom polyfills:**
   - `Element.prototype.scrollIntoView` (no-op stub)
   - `URL.createObjectURL` and `URL.revokeObjectURL` (for blob handling)
   - Suppresses `<a download>` click navigation

3. **MSW server lifecycle:**
   ```typescript
   beforeAll(() => server.listen({ onUnhandledRequest: 'error' }))
   afterEach(() => server.resetHandlers())
   afterAll(() => server.close())
   ```
   `server` is imported from `./handlers` (i.e. `tests/handlers/index.ts`).

4. **Global UI state seeding:** Tests can reset application state before each test:
   ```typescript
   beforeEach(() => useUI.setState({...}))
   ```
   `globals: true` automatically cleans up React Test Library resources after each test, so explicit cleanup is not needed.

## Mock Server (MSW)

Tally uses Mock Service Worker v2 (MSW 2.x) with the `http` and `HttpResponse` API. V1 patterns (`rest` and `res(ctx(...))`) are not used.

### Handler Organization

Handlers are organized one per large resource in `tests/handlers/`:

- `tests/handlers/arg-profiles.ts`
- `tests/handlers/saved-scans.ts`
- etc.

All handler modules export their handlers as an array, which are aggregated in `tests/handlers/index.ts` and passed to `setupServer(...)`. The server is then re-exported for use in the setup file and for per-test overrides.

A shared `tests/handlers/_helpers.ts` provides utilities like:
```typescript
export function errorEnvelope(status: number, code: string, message: string, details?: object) {
  return { code, message, details, status }
}
```

### Error Testing via Sentinel IDs

Instead of overriding handlers per test, Tally uses sentinel IDs documented in handler comments:

```typescript
// Profile ID 999 always returns 404
// Profile ID 888 always returns 409 (conflict)
```

Tests trigger error scenarios by requesting these IDs, reducing boilerplate `server.use()` calls. When a handler must be truly overridden for a specific test, `server.use()` still works.

## Fixtures

Fixtures are plain JSON files organized under `tests/fixtures/<resource>/`:

```
tests/fixtures/
├── arg-profiles/
│   ├── empty.json
│   ├── populated.json
│   └── page-2.json
└── saved-scans/
    ├── empty.json
    └── single-scan.json
```

Fixtures are in the API's real snake_case shape, not TypeScript interfaces. They are typed at the point of use via `as`:

```typescript
const profiles = await import('../../fixtures/arg-profiles/populated.json', { assert: { type: 'json' } })
  .then(m => m.default as Profile[])
```

This approach keeps fixtures simple and avoids duplication between TypeScript types and fixture definitions.

## Render Pattern

There is no shared render helper. Each test file builds its own setup:

```typescript
const queryClient = new QueryClient({
  defaultOptions: {
    queries: { retry: false },
    mutations: { retry: false },
  },
})

function renderPage() {
  return render(
    <QueryClientProvider client={queryClient}>
      <MemoryRouter>
        <YourComponent />
      </MemoryRouter>
    </QueryClientProvider>
  )
}
```

Global UI state (a Zustand store) is seeded directly in `beforeEach`:

```typescript
beforeEach(() => {
  useUI.setState({ theme: 'dark' })
})
```

Auth state is simulated via cookies rather than a provider context, reducing nesting.

## Test File Organization

Tests are split into two categories:

### Unit Tests (`tests/unit/`)

Test pure functions and small presentational components in isolation. No MSW server, no data fetching. Examples:
- Pure utility functions
- Form validation logic
- Components that accept all data via props

### Integration Tests (`tests/integration/`)

Render full pages or complex components against the live MSW server, or use `renderHook` to test data fetching hooks. Integration tests dominate the suite. They interact via `userEvent.setup()`, await results with `screen.findBy*` or `waitFor()`, and override handlers per-test with `server.use()`:

```typescript
it('shows error when fetch fails', async () => {
  server.use(
    http.get('/api/profiles/:id', () => HttpResponse.json({ error: 'Not found' }, { status: 404 }))
  )
  const { getByText } = render(<ProfilePage />)
  await waitFor(() => getByText('Not found'))
})
```

Integration tests can also capture request details to assert query parameters and payloads:

```typescript
let capturedUrl: string | null = null
server.use(
  http.get('/api/profiles', ({ request }) => {
    capturedUrl = request.url
    return HttpResponse.json([...])
  })
)
render(<ProfileList />)
await waitFor(() => {
  expect(capturedUrl).toContain('page=2')
})
```

## Package Versions

| Package | Version | Purpose |
|---------|---------|---------|
| `vitest` | ^4.1.5 | Test runner |
| `@vitest/coverage-v8` | ^4.1.5 | Coverage provider (lockstep with vitest) |
| `msw` | ^2.13.4 (resolves to 2.14.4) | Mock server (v2 API) |
| `@testing-library/react` | ^16.3.2 | Component rendering and queries |
| `@testing-library/user-event` | ^14.6.1 | Realistic user interactions |
| `@testing-library/jest-dom` | ^6.9.1 | DOM matchers |
| `jsdom` | ^29.0.2 (resolves to 29.1.1) | DOM environment |
| `react` | ^19.1.0 | Framework (context) |
| `vite` | ^6.0.7 | Build tool (test config) |
| `typescript` | ^5.7.3 | Type checking |

## Test Scripts

```json
{
  "test": "vitest",
  "test:run": "vitest run",
  "test:ui": "vitest --ui",
  "test:coverage": "vitest run --coverage",
  "validate": "npm run type-check && npm run lint && npm run format:check && npm run test:run"
}
```

## How Physistrong Adapts This

Physistrong deliberately diverges from Tally's layout and patterns in three ways:

1. **Co-located tests:** Physistrong keeps test files next to their source (`resources/js/pages/foo.test.tsx` next to `foo.tsx`), not in a top-level `tests/` tree. The repository already has a root-level `tests/` directory for Laravel's PHP Pest and Dusk suites, so a competing top-level JS `tests/` directory would cause confusion and collision.

2. **Shared render helper:** Physistrong uses a `renderWithProviders` function rather than repeating the provider stack in every test. Its provider stack is richer than Tally's: QueryClient, Router, AuthContext, and an AppProvider for toast notifications. The boilerplate cost of repetition outweighs the documentation benefit of showing setup inline.

3. **Fixture sourcing:** Physistrong fixtures are captured from live HTTP responses against a running deployment rather than maintained as hand-written JSON files. This keeps fixtures in sync with the actual API contract without manual synchronization.

All other patterns from Tally are adopted: MSW v2 for mocking, integration-first testing philosophy, sentinel IDs for error scenarios, and the split between unit and integration tests.
