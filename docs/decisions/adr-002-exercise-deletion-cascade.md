# ADR-002: Deletion Strategy

## Status
Accepted

## Date
2026-06-27

## Deciders
- Justin Christenson (Decision maker and application owner)

---

## Context

Physistrong needs to support deletion of various entities. The complexity
varies by entity type: some deletions are straightforward cascades, others
risk destroying historical training data. The legacy codebase already
enforced a constraint preventing removal of an exercise from a workout when
sets existed (409 response), suggesting the original design valued data
preservation.

---

## Decision

Deletion strategy varies by entity type, based on whether the entity owns
dependent data or is referenced by other entities.

### Workout entries (sets)

**Allow, cascade metrics.** Deleting a workout entry deletes its attached
metric records (load, reps, duration, distance, cardio settings, interval
header/rounds, intensity). These are owned data with no external references.

### Remove exercise from workout (detach pivot)

**Allow, cascade entries.** Removing an exercise from a workout deletes the
pivot record and all workout entries for that exercise in that workout,
along with their metrics. The legacy code blocked this when sets existed.
The new behavior cascades because the user's intent (remove this exercise
from this workout) is unambiguous.

### Delete workout

**Allow, cascade everything.** Deleting a workout deletes all its entries,
entry groups, and associated metrics. A workout is a self-contained session.
If the user deletes it, all the session's data goes with it.

### Delete exercise definition

**Allow only if unused.** An exercise can be deleted only if it has no
workout associations or entries referencing it. If referenced, the API
returns 409 Conflict and the UI presents a disabled delete control with a
tooltip explaining the exercise is in use.

The use case for deletion is correcting mistakes: misspelled names, wrong
type selection, exercises created but never used. Once an exercise appears
in a workout, it becomes part of the training record and cannot be removed.

### Delete equipment type

**Allow only if unused.** An equipment type can be deleted only if no
exercise definitions reference it. Same pattern as exercise deletion: 409
if referenced, disabled UI with tooltip if in use.

---

## Migration Structure

No additional tables needed. Deletion rules are enforced via:

- **ON DELETE CASCADE** on metric table FKs to `workout_entries`
- **ON DELETE CASCADE** on `workout_entries` FK to `workouts`
- **ON DELETE CASCADE** on `entry_groups` FK to `workouts`
- **ON DELETE SET NULL** on `workout_entries` FK to `entry_groups`
- **Application-layer check** for exercise and equipment type deletion
  (query for references before allowing delete)

---

## Consequences

### Positive
- No complex cascade logic for exercise or equipment deletion
- Historical training data is never destroyed by deleting a definition
- Simple UI pattern: delete enabled if unused, disabled with tooltip if in use
- Consistent rule: reference data (definitions, equipment types) is protected
  once in use; session data (workouts, entries) cascades with its parent

### Negative
- Users cannot delete an exercise they no longer use if it has historical data
  (they can rename it, but the record persists)
- No soft delete or archive mechanism (may want to revisit if exercise catalog
  becomes cluttered with old exercises)

---

## Related Decisions

- **ADR-003** (exercise definitions) exercises are the referenced entity
- **ADR-004** (equipment types) same deletion pattern as exercises
- **ADR-006** (composable metrics) metric tables cascade with entries
- **ADR-007** (supersets/circuits) entry groups cascade with workouts

---

## Participants

- Justin Christenson (decision maker)

## Review Date

Review if users report exercise catalog clutter from exercises they no longer
use but cannot delete. An archive feature may be warranted at that point.
