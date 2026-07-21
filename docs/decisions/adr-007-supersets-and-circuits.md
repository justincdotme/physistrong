# ADR-007: Supersets and Circuits

## Status
Accepted

## Date
2026-06-27

## Deciders
- Justin Christenson (Decision maker and application owner)

---

## Context

Supersets, tri-sets, circuits, and giant sets are workout structures where
exercises are grouped and performed in a rotating sequence rather than
completing all sets of one exercise before moving to the next. These are
all variations of the same concept: a group of exercises performed in
rotation for N rounds.

- **Superset**: 2 exercises alternated
- **Tri-set**: 3 exercises alternated
- **Giant set / circuit**: 4+ exercises in sequence, repeated for M rounds

The performance log schema (ADR-006) uses a base `workout_entries` table
with a `set_order` column for global execution sequence. Grouping adds a
second dimension: which group an entry belongs to and which round within
that group.

---

## Decision

Grouping is a **first-class entity** with its own table (`entry_groups`).
The group has independent properties (planned rounds, rest timing) that
don't belong on individual entries.

### Key design choices

**No type column.** "Superset" vs "circuit" vs "giant set" are labels
derived from the group's properties (number of exercises, rest pattern),
not stored values. The UI applies the label.

**No group_order column.** The group has no position property. Its position
in the workout is implicit from the `set_order` of its entries. `set_order`
on `workout_entries` is the single source of truth for execution order
across the entire workout. Standalone entries and grouped entries interleave
naturally.

**No completed_rounds column.** Completion is derived from entries. Count
distinct round numbers with at least one actual value filled in. Avoids
denormalization.

**No nesting.** Groups are flat. An entry belongs to at most one group. A
circuit containing a superset is out of scope for v1.

**ON DELETE SET NULL for group FK.** If a group is deleted, entries become
standalone. The entries represent planned or performed work and should not
disappear because the grouping changed.

**Same structure for templates and instances.** The group table serves both.
When creating a workout from a template, group records are cloned along
with entries. Template entries have targets but null actuals. The user fills
in actuals during the workout.

### How grouping interacts with set_order

`set_order` is the global execution sequence. `entry_group_id` and
`group_round` layer on top:

```
set_order=1:  squat,  group=null, round=null   (standalone)
set_order=2:  squat,  group=null, round=null   (standalone)
set_order=3:  bench,  group=1,    round=1      (superset round 1)
set_order=4:  row,    group=1,    round=1      (superset round 1)
set_order=5:  bench,  group=1,    round=2      (superset round 2)
set_order=6:  row,    group=1,    round=2      (superset round 2)
set_order=7:  plank,  group=null, round=null   (standalone)
```

Rotation order within a round is implicit: entries sharing the same group
and round are ordered by `set_order`.

### Partial completion

**From template:** All entries pre-exist with targets. Incomplete rounds
have null actuals. No placeholder rows needed.

**Ad-hoc:** Entries created as the user goes. If 2 of 3 planned rounds are
done, only those entries exist. The group says `planned_rounds=3` but max
round with entries is 2. Completion derived, not stored.

**Partial within a round:** User does bench but skips row in round 3. From
template: the row entry keeps null actuals. Ad-hoc: only the bench entry
exists for that round.

---

## Migration Structure

```sql
entry_groups
+----- id (PK, auto-increment)
+----- workout_id (FK -> workouts.id, indexed, ON DELETE CASCADE)
+----- name (string, nullable)
+----- planned_rounds (integer, default 1)
+----- rest_between_exercises_seconds (integer, default 0)
+----- rest_between_rounds_seconds (integer, nullable)
+----- created_at, updated_at

-- Changes to workout_entries (replacing group_id placeholder):
-- entry_group_id (FK -> entry_groups.id, nullable, ON DELETE SET NULL)
-- group_round (integer, nullable)
-- INDEX(entry_group_id, group_round, set_order)
```

The compound index on `(entry_group_id, group_round, set_order)` supports
the primary query: all entries for a group organized by round and execution
order.

---

## Edge Cases

- **Same exercise in multiple groups**: Allowed. Bench press can appear in a
  chest/back superset and later in a push/pull circuit in the same workout.
- **Group with one exercise**: Valid but semantically odd (equivalent to "do N
  rounds of this exercise"). Not blocked by the schema.
- **Group deletion**: Entries become standalone (SET NULL). Performed work is
  preserved.
- **Optional name**: User can label groups ("Chest/Back Superset") or leave
  unnamed.

---

## Consequences

### Positive
- Single ordering system (`set_order`) with grouping layered on top
- Group properties (rounds, rest) are normalized in their own table
- Completion tracking derived from entries, no denormalization
- Same structure serves templates and instances
- Supports supersets, tri-sets, circuits, and giant sets with one mechanism

### Negative
- Entries in a group need both `entry_group_id` and `group_round`, adding
  two nullable columns to every workout entry
- Loading a workout with groups requires a join to `entry_groups`
- No nested groups (a circuit containing a superset requires flattening)

---

## Related Decisions

- **ADR-002** (deletion): entry groups cascade with workouts; entries SET NULL on group deletion
- **ADR-003** (exercise definitions): exercise identity is independent of grouping
- **ADR-006** (composable metrics): `workout_entries` table carries the group FK and round columns

---

## Participants

- Justin Christenson (decision maker)

## Review Date

Review after initial implementation to verify that the flat grouping model
handles real workout structures without friction.
