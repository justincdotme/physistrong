# ADR-010: System-Owned Exercises via Nullable user_id

## Status
Accepted

## Date
2026-06-27

## Deciders
- Justin Christenson (Decision maker and application owner)

---

## Context

Physistrong requires a shared, read-only exercise catalog to reduce user setup friction. The application is seeding approximately 873 exercises from the free-exercise-db open dataset as a starter library available to every user.

During initial planning (ADR-003), exercises were modeled as strictly per-user: the `exercises` table carried a non-null `user_id` with a `UNIQUE(user_id, name)` constraint. This design enforced per-user ownership and per-user name uniqueness, but it provided no mechanism for system-seeded, shared exercise definitions.

The equipment-type model (ADR-004) already solved an analogous problem: `equipment_types.user_id` is nullable, with null indicating a system-seeded record. This established a pattern that exercises can follow.

Without system-owned exercises, every user must create their own definitions for common exercises (bench press, squat, deadlift, etc.), duplicating effort and setup burden. With a seeded catalog, users can immediately see and use 873 standard exercises and optionally create custom ones.

---

## Decision

**Exercises carry a nullable `user_id` field.** A null `user_id` indicates a system-owned exercise (read-only catalog). A non-null `user_id` indicates a user-owned custom exercise. This pattern mirrors ADR-004 exactly.

### Amend ADR-003

The `exercises` table schema changes:
- `user_id` becomes nullable (was non-null with NOT NULL constraint)
- `UNIQUE(user_id, name)` constraint remains and continues to enforce per-user name uniqueness

### MySQL UNIQUE Index Behavior with NULL

MySQL treats NULL values as distinct in unique indexes: two rows with NULL user_id do NOT violate a `UNIQUE(user_id, name)` constraint. This means name uniqueness is NOT enforced across system exercises. This is acceptable because:
- Seed data is pre-deduplicated; all 873 exercise names are unique
- System exercises are read-only; users cannot create duplicates
- Per-user uniqueness for custom exercises is still enforced (both rows with the same user_id and name would violate the constraint)

### Authorization (ExercisePolicy)

- **Read:** Users can read their own exercises plus all system exercises (null user_id)
- **Create:** Sets `user_id` to the authenticated user
- **Update / Delete:** Users can only update/delete their own exercises (non-null user_id matching the authenticated user)
- **System exercises are read-only:** Users cannot modify system exercises via the API

### Deletion (ADR-002 interaction)

- User-owned exercises follow ADR-002: block-if-used (return 409 if referenced by a workout entry)
- System exercises are not user-deletable and have no block-if-used logic (the API does not expose a delete endpoint for system exercises)

---

## Migration Structure

Amend the `exercises` migration from ADR-003:

```sql
exercises
├── id (PK, auto-increment)
├── user_id (FK → users.id, nullable, indexed)  -- null = system-owned
├── equipment_type_id (FK → equipment_types.id, nullable, indexed)
├── name (string)
├── type (string, indexed, discriminator)
├── notes (text, nullable)
├── created_at, updated_at
└── UNIQUE(user_id, name)
```

The `user_id` constraint changes from NOT NULL to nullable. The unique index `UNIQUE(user_id, name)` is unchanged; it continues to prevent duplicate names per user while permitting multiple system exercises to share a name with null user_id.

---

## Consequences

### Positive
- Seeded exercise catalog available to every user at no setup cost
- Pattern consistency: mirrors ADR-004 (equipment_types.user_id nullable)
- Users can still create custom exercises (non-null user_id)
- Per-user name uniqueness is preserved; custom exercises cannot duplicate user-owned names
- Simple authorization: null user_id is the sole authoritative marker for system ownership

### Negative
- System exercise catalog is not user-editable or customizable per user
- If seed data contains an exercise users consider incorrectly named or typed, the application must publish a correction (no user override)
- Seeding must ensure all 873 exercise names are globally unique across types (no duplicates)

### New Decisions Required
- Seeder script: insert 873 exercises with `user_id = NULL`
- ADR-003 migration specification must be updated (see Migration Structure above)
- Project CLAUDE.md must be updated: change "No shared data except system-seeded equipment types" to "No shared data except system-seeded equipment types and exercises"

---

## Alternatives Considered

**is_system boolean flag.** The `equipment_types` table (ADR-004) carries both a nullable `user_id` AND an `is_system` boolean. We chose to use null `user_id` as the sole marker (not carry `is_system`) to keep the system-ownership signal unambiguous and to mirror the simplest variant of the equipment-type pattern. A single source of truth (null user_id) reduces the risk of contradiction (is_system=true but user_id is non-null).

---

## Related Decisions

- **ADR-002** (deletion): block-if-used applies to user-owned exercises; system exercises are read-only
- **ADR-003** (exercise definitions): this decision amends the schema from ADR-003; the migration spec must be updated
- **ADR-004** (gym equipment): the pattern (nullable user_id for system ownership) mirrors this decision exactly
- **Phase 2 seeding work:** requires a seeder script that inserts 873 exercises with `user_id = NULL`

---

## Influences

- The equipment-type model (ADR-004) successfully demonstrated nullable `user_id` for system ownership
- User feedback on setup friction: every user recreating the same 100 common exercises
- Free-exercise-db provides a pre-curated, deduplicated catalog of 873 exercises
- MySQL unique index behavior: null is distinct in unique constraints, suitable for this use case

---

## Participants

- Justin Christenson (decision maker)

## Review Date

Review after Phase 2 seeding is complete and users have exercised the system-owned catalog. Revisit if users request per-user customization of system exercises or if catalog errors are discovered.
