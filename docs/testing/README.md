# Testing

## Frontend fixtures

The MSW fixtures under `resources/js/test/mocks/fixtures/` are captured from
a real running API, never hand-written and never hand-edited. If a fixture
looks wrong or an API contract changes, regenerate the set; do not patch
JSON by hand.

## Regenerating fixtures

```bash
node scripts/capture-fixtures.mjs
```

Run it from the repo root on the host. The script is self-contained and safe
to run unattended:

- It runs under its own Docker Compose project (`physistrong-capture`) on an
  isolated network. The dev stack can stay up; the dev database is not
  reachable from the capture containers.
- It migrates and seeds an ephemeral `physistrong_dusk` database on
  `test-mysql`, refuses to run if the connected database is anything else,
  and tears everything down afterward (including on failure).
- It registers a throwaway user through the API, creates data covering every
  exercise type and metric dimension, captures the responses, and sanitizes
  timestamps and PII before writing.

A run regenerates every captured fixture in sequence (about 2 minutes).
Four static error fixtures (`equipment/in-use-error.json`,
`exercises/forbidden.json`, `exercises/in-use-error.json`,
`workouts/forbidden.json`) are not touched by the script.

Each fixture is written as its capture step completes, not staged and
swapped in at the end, so a run that fails partway leaves some fixtures
regenerated and others stale. After every run, successful or not, review
`git diff resources/js/test/mocks/fixtures/` and confirm only expected
changes appear. Pagination URLs embed the capture server's random host
port; that churn is harmless.

If a crashed run leaves containers behind:

```bash
docker compose -p physistrong-capture --profile testing down --volumes --remove-orphans
```

## Test suites

- PHP: `bin/test` (PHPUnit, sqlite in-memory, no Docker services needed).
- Frontend: `docker compose exec app npm run test` (Vitest + MSW).
- Browser: Dusk via the `dusk-testing` skill lifecycle only; see
  `.claude/CLAUDE.md` for the safety rules.
