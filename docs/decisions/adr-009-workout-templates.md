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
principle applies to the copy workout feature (PS-52, deferred).

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

The copy workout feature (PS-52, deferred) follows the same principle:
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

template_exercises
+----- id (PK)
+----- template_id (FK -> workout_templates.id, ON DELETE CASCADE)
+----- exercise_id (FK -> exercises.id)
+----- exercise_order (integer)
+----- created_at, updated_at
+----- INDEX(template_id, exercise_order)

template_entry_groups
+----- id (PK)
+----- template_id (FK -> workout_templates.id, ON DELETE CASCADE)
+----- name (string, nullable)
+----- planned_rounds (integer, default 1)
+----- rest_between_exercises_seconds (integer, default 0)
+----- rest_between_rounds_seconds (integer, nullable)
+----- created_at, updated_at

template_group_exercises
+----- id (PK)
+----- template_entry_group_id (FK -> template_entry_groups.id, ON DELETE CASCADE)
+----- exercise_id (FK -> exercises.id)
+----- exercise_order (integer)
+----- created_at, updated_at
```

`template_exercises` holds standalone exercises (not in a group).
`template_group_exercises` holds exercises within a group, with their order
within the rotation. Between the two, the full exercise structure is
defined.

---

## Consequences

### Positive
- Clean separation: templates and workouts are independent entities
- Deleting a template never affects existing workouts
- No orphan references or cascade complexity
- Same clone-and-detach pattern works for copy workout (PS-52)

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
