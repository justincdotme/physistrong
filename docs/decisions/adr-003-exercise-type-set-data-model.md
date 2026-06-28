# ADR-003: Exercise Type Set Data Model

## Status
Accepted

## Date
2026-06-27

## Deciders
- Justin Christenson (Decision maker and application owner)

---

## Context

Physistrong is a self-hosted fitness tracking app built on Laravel 13, MySQL, and React. The application needs to support multiple exercise types, each with different measurement requirements:

- **Weight + reps exercises** (bench press, curls, squats): require weight and repetition count per set
- **Hold/duration exercises** (planks, wall sits, handstand holds): require time held in seconds per set
- **Interval exercises** (HIIT, Tabata): require interval count and duration per interval per set

The legacy data model uses a single `sets` table with fixed columns (`weight`, `count`, `set_order`) that must accommodate all exercise types. This violates database normalization: sets from duration-based exercises contain meaningless null values in the weight and count columns, and interval-based exercises cannot properly record intervals or interval duration at all.

A concrete example of this problem: the legacy code represented body weight exercises (pull-ups, dips) by storing weight as 0 or negative, with a model accessor converting that to the string "Body." This magic number convention exists because the single-shape set table has no way to express "this exercise type doesn't use weight." With a proper type-aware data model, a body weight exercise type would not include weight in its measurement schema.

Adding support for new exercise types under this model requires widening the table further, creating more null columns and more validation burden at the application layer.

This data modeling decision blocks implementation of exercise types and set recording functionality. The chosen approach will constrain how exercise type definitions are structured, how set measurements are validated, and how set data is queried for reporting and analytics.

---

## Decision

Use **Class Table Inheritance (CTI)** to model exercise definitions. A base `exercises` table holds universally shared columns (name, notes, user ownership, timestamps). Type-specific child tables FK back to `exercises.id` and carry only the columns relevant to that type.

This decision applies to exercise **definitions** (template/reference data). Performance logging (recorded values of work performed) is a separate concern with its own schema design (forthcoming ADR).

**Why CTI over the alternatives:**

- **Over nullable columns (STI):** Normalization violation. Attributes depend on PK + type, not PK alone. Every row carries dead weight for columns that don't apply.
- **Over JSON column:** Loses database-level type safety and constraints. Cannot enforce required fields per type at the schema level.
- **Over EAV:** Anti-pattern. Hard to query, type-unsafe, performance degradation.

**Why explicit foreign keys over Laravel morphTo/morphMany:**

- Foreign keys enforceable at the database level (morphTo cannot do this)
- No string-to-class mapping that breaks on refactors
- Discriminator column on base table tells which child table to join
- Standard Eloquent `hasOne`/`belongsTo` relationships

---

## Type Categories

Exercise types have been restructured from 9 to 4 based on shared attribute patterns:

1. **Resistance** (bench press, pull-ups, curls, banded squats, box jumps). Covers both weighted and bodyweight exercises. Bodyweight is a flag, not a separate type. Plyometrics fold in here too.
2. **Timed hold** (plank, wall sit, dead hang). Sustained duration exercises.
3. **Distance/time** (treadmill, outdoor run, rowing, swimming, parachute run). Distance + time tracking.
4. **Interval** (HIIT, Tabata). Structured work/rest periods.

**Merges made:**
- Weighted + Bodyweight merged (both are reps against resistance, difference is load source)
- Plyometric folded into Resistance (rep-based, body weight)
- Swimming folded into Distance/time (stroke type captured in exercise name/notes if needed)
- Agility/drill dissolved (ladder drills → rep-based resistance, parachute runs → distance/time)
- Resistance band/rope dissolved (resistance mapping is an equipment concern per ADR-004, exercises are resistance type)

---

## Migration Structure

```sql
exercises
├── id (PK, auto-increment)
├── user_id (FK → users.id, nullable, indexed)  -- null = system-owned (ADR-010)
├── equipment_type_id (FK → equipment_types.id, nullable, indexed)
├── name (string)
├── type (string, indexed, discriminator)
├── notes (text, nullable)
├── created_at, updated_at
└── UNIQUE(user_id, name)

exercise_resistance
├── id (PK)
├── exercise_id (FK → exercises.id, unique, ON DELETE CASCADE)
├── bodyweight_base (boolean, default false)
├── allows_added_weight (boolean, default false)
├── bilateral (boolean, default true)
└── created_at, updated_at

exercise_timed_hold
├── id (PK)
├── exercise_id (FK → exercises.id, unique, ON DELETE CASCADE)
├── target_duration_seconds (integer, nullable)
└── created_at, updated_at

exercise_distance
├── id (PK)
├── exercise_id (FK → exercises.id, unique, ON DELETE CASCADE)
├── distance_unit (enum: 'meters', 'kilometers', 'miles', 'yards')
├── tracks_elevation (boolean, default false)
└── created_at, updated_at

exercise_interval
├── id (PK)
├── exercise_id (FK → exercises.id, unique, ON DELETE CASCADE)
├── default_work_seconds (integer, nullable)
├── default_rest_seconds (integer, nullable)
├── default_rounds (integer, nullable)
└── created_at, updated_at
```

Each child table's `exercise_id` FK is UNIQUE (one-to-one) and cascades on delete.

---

## Edge Cases Documented

- **Bodyweight + added weight**: Not a separate type. Resistance with `bodyweight_base=true`, `allows_added_weight=true`. Performance log records the ADDED weight.
- **Cardio on machine vs outdoors**: Same exercise type (distance/time), different equipment type. "Treadmill Run" and "Outdoor Run" are separate exercises because their equipment type differs.
- **Custom user-defined types**: Users create exercises within existing categories. New categories require development (new child table + migration).
- **Bilateral flag**: Tracks whether exercise works both sides simultaneously or one side at a time.
- **Equipment type on definition**: The exercise definition references an equipment type (nullable FK to `equipment_types`). Different equipment = different exercise (dumbbell curl != barbell curl != cable curl). See ADR-004.
- **Body weight magic number**: The legacy convention of weight<=0 meaning "Body" is eliminated. Bodyweight exercises are properly typed; their performance log omits the external weight field unless `allows_added_weight` is true.

## Consequences

### Positive
- Normalized schema with no dead columns per row
- Type-safe at the database level, with constraints enforced at the schema layer
- Clean separation of concerns: exercise definitions separate from performance logging
- Adding a new exercise of an existing category requires no migration
- Clear Eloquent relationships with standard `hasOne`/`belongsTo` patterns

### Negative
- Adding a new exercise category requires a migration (acceptable tradeoff for type safety)
- Querying across exercise types requires joins (single-user scale makes this not a concern)

### New Decisions Required
- ADR-006 (composable metrics performance log) composes with this definition schema

---

## Influences

- The current legacy model violates normalization, which prompted this evaluation
- Physistrong's single-user, self-hosted nature reduces scale concerns
- Laravel 13 and MySQL 8.0+ both have strong support for CTI via standard Eloquent relationships
- Class Table Inheritance pattern is well-established in domain-driven design and database modeling literature

---

## Related Decisions

- **ADR-002** (deletion): exercises block-if-used, cannot delete if referenced
- **ADR-004** (gym equipment): equipment is a type catalog referenced by exercise definitions
- **ADR-005** (weight unit): raw numbers in user's configured unit
- **ADR-006** (composable metrics): performance logging schema that composes with these definitions
- **ADR-010** (system-owned exercises): amends this schema to make user_id nullable, where null marks a system-owned catalog row

---

## Participants

- Justin Christenson (decision maker)

## Review Date

Review after initial implementation to verify CTI and composable metrics interact cleanly in practice.
