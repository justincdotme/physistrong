# ADR-009: Workout Templates

## Status
Accepted

## Date
2026-06-27

## Deciders
- Justin Christenson (Decision maker and application owner)

---

## Context

Workout templates are an MVP requirement (PS-54). Users save reusable
routines and create workout instances from them. The same clone-and-detach
principle applies to the copy workout feature (PS-52, implemented; see
Amendment below).

---

## Decision

### Separate table

Templates are a separate entity (`workout_templates`), not a flag on the
`workouts` table. A workout has a required `date` field. A template has no
date. Mixing them in one table would require a nullable date and
conditional logic everywhere.

### Deletable

Templates are always deletable. Once a workout is created from a template,
the workout is an independent record with no FK back to the template.
Deleting a template has no effect on workouts that were created from it.

### Clone and detach

Creating a workout from a template clones the template's structure into a
new workout:
1. Create a new `workouts` record with name (from template) and date
   (user-provided)
2. Clone `template_exercises` into the workout's exercise pivot
3. Clone `template_entry_groups` into `entry_groups`
4. Generate `workout_entries` from the exercise list and group structure
   (one entry per exercise per round per group)

After cloning, the workout has no reference to the template. Editing the
template later does not affect existing workouts, and deleting the template
does not affect them either.

The copy workout feature (PS-52, implemented; see Amendment below)
follows the same principle:
clone the workout's structure into a new independent workout.

### Template structure

A template stores:
- Which exercises, in what order, with what equipment (via exercise
  definitions)
- Which exercises are grouped (supersets/circuits) with rounds and rest
  timing

---

## Migration Structure

```sql
workout_templates
+----- id (PK, auto-increment)
+----- user_id (FK -> users.id, indexed)
+----- name (string)
+----- notes (text, nullable)
+----- created_at, updated_at

template_entry_groups
+----- id (PK)
+----- template_id (FK -> workout_templates.id, ON DELETE CASCADE)
+----- name (string, nullable)
+----- planned_rounds (integer, default 1)
+----- rest_between_exercises_seconds (integer, default 0)
+----- rest_between_rounds_seconds (integer, nullable)
+----- created_at, updated_at

template_exercises
+----- template_id (FK -> workout_templates.id, ON DELETE CASCADE)
+----- exercise_id (FK -> exercises.id, ON DELETE RESTRICT)
+----- exercise_order (integer)
+----- template_entry_group_id (FK -> template_entry_groups.id, nullable, ON DELETE SET NULL)
+----- PRIMARY KEY (template_id, exercise_id)
+----- INDEX (template_id, exercise_order)
```

A single `template_exercises` pivot holds all exercises regardless of group
membership. The nullable `template_entry_group_id` optionally links an
exercise to a group. Ungrouped exercises have NULL in this column.

---

## Amendment: Single-Pivot Template Exercise Structure

**Date:** 2026-07-03

**Amended by:** PS-112 (documents implementation decision from Phase 7a,
discovered during 2026-07-02 backend review)

The original design specified three tables: `template_exercises` for
standalone exercises, `template_entry_groups` for group definitions, and
`template_group_exercises` for exercises within groups. The implementation
merged the exercise tables into a single `template_exercises` pivot with a
nullable `template_entry_group_id` FK.

**Rationale:**

1. **ADR-007 consistency.** The workout side uses the same pattern:
   `workout_entries.entry_group_id` is a nullable FK, with all entries in
   one table regardless of group membership.
2. **Composite-key duplicate prevention.** The `(template_id, exercise_id)`
   primary key prevents attaching the same exercise twice.
3. **SET NULL semantics.** Deleting a group sets the FK to NULL rather than
   cascading exercise deletion, matching ADR-007's group-deletion behavior.

---

## Amendment: Copy Workout Implemented

**Date:** 2026-07-17

**Amended by:** PS-156 (documentation drift found during a codebase audit)

Copy workout, noted above as deferred (PS-52), shipped.
`App\Services\WorkoutCloneService` is shared by both flows:
`fromTemplate()` clones a template into a workout (this ADR's original
scope), `fromWorkout()` clones an existing workout into a new
independent one via `POST /workouts/{workout}/copy`. Both follow the
same clone-and-detach principle: the result carries no FK back to its
source.

---

## Amendment: Pivot Table Naming Convention

**Date:** 2026-07-17

**Amended by:** PS-141 (naming convention for future pivot tables)

Future pivot tables use a domain-plural name, matching
`template_exercises` above rather than Laravel's default
alphabetical-singular convention. `exercise_workout` (the workout's
exercise pivot, referenced in Decision above) predates this
convention and keeps its existing name.

---

## Consequences

### Positive
- Clean separation: templates and workouts are independent entities
- Deleting a template never affects existing workouts
- No orphan references or cascade complexity
- Same clone-and-detach pattern works for copy workout (PS-52),
  implemented via the shared `WorkoutCloneService`

### Negative
- Template tables partially mirror workout structure (exercises pivot,
  groups)
- No link from workout back to template (cannot answer "which template
  was this created from?" after the fact)

---

## Related Decisions

- **ADR-006** (composable metrics): workout_entries generated during clone
- **ADR-007** (supersets/circuits): entry_groups cloned from template groups
- **ADR-002** (deletion): templates are always deletable, no block-if-used

---

## Participants

- Justin Christenson (decision maker)

## Review Date

Review during implementation to verify the clone flow works cleanly and
the template table structure captures everything users need in a routine.
