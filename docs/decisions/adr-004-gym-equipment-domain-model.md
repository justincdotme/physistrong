# ADR-004: Gym Equipment Domain Model

## Status
Accepted

## Date
2026-06-27

## Deciders
- Justin Christenson (Decision maker and application owner)

---

## Context

Physistrong users need to specify what equipment was used for an exercise
because the same movement on different equipment produces fundamentally
different training data. A 100 lb overhead press on a lever machine is
biomechanically different from a 100 lb overhead press with freeweights. A
dumbbell curl, barbell curl, and cable curl are different exercises.

The legacy codebase had five CRUD tickets (PS-27, PS-28, PS-30, PS-31, PS-35)
with no implementation. Equipment is an MVP requirement.

---

## Decision

Equipment is a **type catalog** referenced by exercise definitions. Equipment
types are categories of gear (barbell, dumbbell, cable machine, lever machine,
treadmill, stationary bike, rower, resistance band, etc.), not specific
instances of machines at a gym.

### Attachment point: exercise definition

The exercise definition carries an `equipment_type_id` FK (nullable, for
exercises like planks that don't use equipment). Different equipment types
produce different exercises: "Dumbbell Curl" and "Cable Curl" are separate
exercise records referencing different equipment types. No equipment tracking
at log time; the exercise identity already carries the equipment context.

### Catalog governance: seeded defaults, user-extensible

The application seeds common equipment types (barbell, dumbbell, cable machine,
lever machine, treadmill, stationary bike, rower, resistance band, kettlebell,
etc.). Users can create custom equipment types for niche or home gym gear. A
`is_system` flag distinguishes seeded from user-created types.

### Granularity: type only

Equipment records represent categories ("Cable Machine"), not specific
instances ("Cable Machine #3 in the corner"). Tracking individual machines
at a gym is unnecessary for this application.

### Relationship to exercise types: orthogonal

No restriction between equipment types and exercise types. The user can
combine any equipment with any exercise type. This aligns with the self-hosted
philosophy of trusting the user to make their own decisions.

### Deletion: follows ADR-002

Equipment deletion follows ADR-002: allow only if unused, 409 if referenced. Equipment referenced by exercise
definitions cannot be silently removed.

---

## Migration Structure

```sql
equipment_types
+----- id (PK, auto-increment)
+----- name (string)
+----- user_id (FK -> users.id, nullable, ON DELETE CASCADE) -- null = system-seeded; custom types removed with their owner
+----- is_system (boolean, default false)
+----- created_at, updated_at
+----- UNIQUE(name, user_id) -- prevent per-user duplicates
```

The `exercises` base table (ADR-003) carries:
```sql
equipment_type_id (FK -> equipment_types.id, nullable, indexed)
```

---

## Consequences

### Positive
- Equipment type is part of exercise identity, eliminating per-set data entry
- Simple schema: one table for the catalog, one FK on exercise definitions
- Seeded defaults reduce setup friction for common equipment
- User-extensible for niche equipment without requiring a deployment

### Negative
- Cannot track which specific machine at a gym (type only, no instances)
- Exercise catalog grows with equipment variants ("Barbell Bench Press" and
  "Dumbbell Bench Press" are separate exercises)

### New Decisions Required
- Seed list: finalize which equipment types ship by default
- ADR-002 (deletion cascade) applies to equipment deletion

---

## Influences

- User confirmed: dumbbell curl, barbell curl, and cable curl are different
  exercises, not the same exercise on different equipment
- Equipment type matters because identical load on different equipment produces
  different training stimulus
- Specific machine instance tracking is unnecessary ("it doesn't matter if the
  user used the Acme machine or the SuperDude2000 machine")

---

## Related Decisions

- **ADR-002** (deletion cascade) applies to equipment type deletion
- **ADR-003** (exercise definitions) carries the `equipment_type_id` FK
- **ADR-005** (weight unit) applies to any weight values on equipment
- **ADR-006** (composable metrics) no equipment_id on log entries

---

## Participants

- Justin Christenson (decision maker)

## Review Date

Review if multi-gym support is added or if users report needing instance-level
equipment tracking.
