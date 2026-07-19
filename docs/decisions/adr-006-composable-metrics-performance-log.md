# ADR-006: Composable Metrics Performance Log

## Status
Accepted

The `log_distance_metrics.distance_unit` column is superseded by
[ADR-014](adr-014-unitless-distance-values.md); the column was dropped
on 2026-07-03.

## Date
2026-06-27

## Deciders
- Justin Christenson (Decision maker and application owner)

---

## Context

Recording the actual values of work performed is more complex than defining
exercise types (ADR-003). A single workout entry can involve multiple
orthogonal metric dimensions simultaneously:

- **HIIT on a rowing machine** needs interval metrics AND cardio settings AND
  distance AND intensity
- **Weighted exercise** needs load AND reps
- **Treadmill interval training** needs interval metrics AND cardio settings
  AND distance AND intensity
- **A simple plank** needs only duration

If we use single inheritance (one log type per entry), we're forced to pick
one type and end up back at nullable columns or duplicated columns across
child tables. The metrics **compose** rather than **inherit**.

---

## Decision

Use **composable metric sets** for performance logging. A base
`workout_entries` table records what was done (which exercise, in which
workout, in what order). Independent metric tables attach via direct FK,
each capturing one dimension of measurement. An entry can have any
combination of metric records.

### Attachment mechanism: direct FKs

Each metric table has an `entry_id` FK (UNIQUE, one-to-one per dimension)
back to `workout_entries`. The exercise definition's type (from ADR-003)
drives which metric tables are expected. No polymorphic strings, no pivot
tables.

### Programmed vs. actual: column-level

Each metric table carries nullable `target_*` columns alongside nullable
`actual_*` columns. Target is null for ad-hoc work. Actual is null for
not-yet-performed entries (supporting the template/copy workflow). A workout
is implicitly "complete" when all entries have actuals filled in.

### No equipment on log entries

Equipment type is on the exercise definition (ADR-004). The exercise identity
already carries equipment context. No `equipment_id` on `workout_entries`.

### Subjective metrics at workout level

Subjective metrics (exhaustion, soreness) are recorded at the workout level,
not per-entry. Per-entry intensity tracking (RPE, heart rate) was
deferred at this decision's original scope; it shipped later, see
Amendment below.

---

## Metric Dimension Mapping

Which metrics attach to which exercise types. The application layer
(config registry) enforces an **allowed** set per exercise type, not
the schema. The required column below documents an expectation only;
presence is not enforced, see Amendment below.

| Exercise type | Required metrics | Optional metrics |
|--------------|-----------------|-----------------|
| Resistance | load, reps |  |
| Timed hold | duration | load (weighted holds) |
| Distance/time | distance | duration, cardio settings |
| Interval | interval header + rounds | cardio settings, distance |

Intensity (RPE, heart rate) is now optional for all four exercise
types, see Amendment below.

---

## Migration Structure

```sql
workout_entries
+----- id (PK, auto-increment)
+----- workout_id (FK -> workouts.id, indexed, ON DELETE CASCADE)
+----- exercise_id (FK -> exercises.id, indexed)
+----- set_order (integer)
+----- entry_group_id (FK -> entry_groups.id, nullable, ON DELETE SET NULL)
+----- group_round (integer, nullable)
+----- notes (text, nullable)
+----- created_at, updated_at
+----- INDEX(workout_id, set_order)
+----- INDEX(exercise_id, created_at) -- progress tracking queries
+----- INDEX(entry_group_id, group_round, set_order)

log_load_metrics
+----- id (PK)
+----- entry_id (FK -> workout_entries.id, unique, ON DELETE CASCADE)
+----- target_weight (decimal 8,2, nullable) -- raw number in user's configured unit (ADR-005)
+----- actual_weight (decimal 8,2, nullable)
+----- bodyweight_only (boolean, default false)
+----- created_at, updated_at

log_rep_metrics
+----- id (PK)
+----- entry_id (FK -> workout_entries.id, unique, ON DELETE CASCADE)
+----- target_reps (integer, nullable)
+----- actual_reps (integer, nullable)
+----- to_failure (boolean, default false)
+----- failure_rep (integer, nullable)
+----- created_at, updated_at

log_duration_metrics
+----- id (PK)
+----- entry_id (FK -> workout_entries.id, unique, ON DELETE CASCADE)
+----- target_duration_seconds (integer, nullable)
+----- actual_duration_seconds (integer, nullable)
+----- created_at, updated_at

log_distance_metrics
+----- id (PK)
+----- entry_id (FK -> workout_entries.id, unique, ON DELETE CASCADE)
+----- target_distance (decimal 10,2, nullable)
+----- actual_distance (decimal 10,2, nullable)
+----- distance_unit (enum: 'meters', 'kilometers', 'miles', 'yards')
+----- lap_count (integer, nullable)
+----- stroke_count (integer, nullable)
+----- created_at, updated_at

log_cardio_settings
+----- id (PK)
+----- entry_id (FK -> workout_entries.id, unique, ON DELETE CASCADE)
+----- resistance_level (integer, nullable)
+----- incline (decimal 5,2, nullable)
+----- speed (decimal 5,2, nullable)
+----- cadence (integer, nullable)
+----- created_at, updated_at

log_interval_headers
+----- id (PK)
+----- entry_id (FK -> workout_entries.id, unique, ON DELETE CASCADE)
+----- programmed_rounds (integer, nullable)
+----- completed_rounds (integer, nullable)
+----- target_work_seconds (integer, nullable)
+----- target_rest_seconds (integer, nullable)
+----- created_at, updated_at

log_interval_rounds
+----- id (PK)
+----- interval_header_id (FK -> log_interval_headers.id, ON DELETE CASCADE)
+----- round_number (integer)
+----- actual_work_seconds (integer, nullable)
+----- actual_rest_seconds (integer, nullable)
+----- heart_rate_avg (integer, nullable)
+----- heart_rate_peak (integer, nullable)
+----- created_at, updated_at
+----- UNIQUE(interval_header_id, round_number)

workouts
+----- id (PK, auto-increment)
+----- [other columns...]
+----- exhaustion (integer, nullable) -- subjective exhaustion rating for the whole workout
+----- soreness (integer, nullable) -- subjective soreness rating for the whole workout
+----- [timestamps and indexes...]
```

---

## Query Patterns

Progress tracking (bench press weight over 3 months):
```sql
SELECT we.created_at, lm.actual_weight
FROM workout_entries we
JOIN log_load_metrics lm ON lm.entry_id = we.id
WHERE we.exercise_id = ? AND we.created_at >= ?
ORDER BY we.created_at
```

Clean, indexable, no JSON parsing or polymorphic type resolution.

---

## Edge Cases

- **Unplanned exercise**: Log entry with null targets, only actuals recorded.
  No structural problem.
- **Drop sets**: Three weight changes in rapid succession = three separate
  workout entries, each with their own load + rep metrics, distinguished by
  set_order.
- **Partial completion**: Fewer entries than planned, or actual < target on
  entries that exist. Natural representation.
- **Resistance band resolution**: The log stores the resolved resistance
  value (converted to canonical KG), not the band color code. The equipment
  type (ADR-004) provides context. Historical data stays accurate.
- **Cardio settings scope**: `log_cardio_settings` is only for
  cardio/endurance equipment (treadmill incline, rower damper, bike
  resistance). For cable machines, the "resistance" is the weight, already
  captured in `log_load_metrics`.
- **Template/copy flow**: Entries created from a template have targets but
  null actuals. User fills in actuals during the workout.
- **Supersets/circuits**: `entry_group_id` and `group_round` on
  `workout_entries` with a dedicated `entry_groups` table. See ADR-007.

---

## Amendment: Intensity Metrics Shipped

**Date:** 2026-07-17

**Amended by:** PS-156 (documentation drift found during a codebase audit)

Per-entry intensity tracking shipped: `log_intensity_metrics` attaches to
`workout_entries` via `entry_id`, same as the other metric tables. Unlike
the rest, it has no target/actual split (`rpe`, `heart_rate_avg`,
`heart_rate_peak` only): intensity records how a set felt, not what was
planned. It is optional for all four exercise types
(`ExerciseType::allowedMetrics()`), bringing the metric table count to
8 plus the 1 child table.

The "Required metrics" column above documents an expectation; it was
never enforced. `ExerciseType::requiredMetrics()` exists for
documentation only. Validation checks submitted metrics against
`allowedMetrics()` (required plus optional) and rejects anything
outside that set, so ad-hoc entries and template-derived targets can
stay partial.

---

## Consequences

### Positive
- Fully normalized: no dead columns, no nullable column sprawl
- Composable: any combination of metrics attaches naturally
- Clean separation from exercise definitions (ADR-003)
- Efficient queries for progress tracking and analytics
- Supports template/copy flow via nullable actuals

### Negative
- Loading a full workout requires joining multiple metric tables (mitigated
  by knowing which tables to join from exercise type)
- More tables than a single-table approach (8 metric tables + 1 child; intensity added, see Amendment below)
- Adding a new metric dimension requires a new table and migration

---

## Related Decisions

- **ADR-003** (exercise definitions, CTI) provides the type discriminator
  that drives which metrics to expect
- **ADR-004** (equipment types) on exercise definition, not on log entries
- **ADR-002** (deletion) workout entries cascade with workouts; definitions block-if-used
- **ADR-005** (weight unit) raw numbers in user's configured unit, no canonical conversion
- **ADR-007** (supersets/circuits) grouping via `entry_groups` table

---

## Participants

- Justin Christenson (decision maker)

## Review Date

Review after initial implementation to verify composable metrics perform
well for real workout logging and progress tracking queries.
