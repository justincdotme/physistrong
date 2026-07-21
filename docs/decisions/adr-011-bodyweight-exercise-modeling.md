# ADR-011: Bodyweight Exercise Modeling

## Status
Proposed

## Date
2026-06-27

## Deciders
- Justin Christenson (Decision maker and application owner)

---

## Context

Physistrong currently models bodyweight exercises thinly across two layers. At the exercise definition level (ADR-003), the `exercise_resistance` table carries two booleans: `bodyweight_base` (the movement is fundamentally bodyweight, e.g., pull-up, push-up) and `allows_added_weight` (extra load can be hung off it, e.g., weighted pull-up). At the performance logging level (ADR-006), `log_load_metrics` carries a `bodyweight_only` flag and target/actual weight columns.

This works for basic cases: a pull-up is marked `bodyweight_base=true` and `allows_added_weight=true`, and the log stores only the added weight. But several real-world cases fall short:

- **Assisted bodyweight** (band-assisted or machine-assisted pull-ups/dips): the assistance is effectively negative load. A flag cannot express "bodyweight minus 40 lb of band assistance", and the log has no way to record or analyze the actual working load.

- **Added-weight bodyweight volume and PRs**: a weighted pull-up's true working load is bodyweight plus added weight. The log stores only the added weight. Volume and PR math that ignores bodyweight understates the real load and makes progress analytics incomplete.

- **Bodyweight as a load variable for analytics**: progress queries (ADR-006 patterns) and future Phase 9 progress tracking may want the user's bodyweight at the time of the set to compute true load = bodyweight +/- added/assist. Bodyweight changes over time, implying the need to capture it per workout or per set, not only as a static user profile value.

- **Percentage-of-bodyweight or relative-strength views**: some users track lifts relative to bodyweight (e.g., "can I lift 1.5x my weight?"). The current schema does not support this kind of relative analysis.

- **Legacy antipattern avoidance**: the old code used weight <= 0 to mean "Body" (ADR-003 context). ADR-003 deliberately eliminated this. Any new approach must not reintroduce magic-value conventions.

This design gap does not block current work (Phase 2 seed data sets `bodyweight_base` heuristically, which is sufficient for now), but it will constrain Phase 9 (progress tracking) analytics and will require a decision before deep work begins there.

---

## Decision

No decision has been made yet. This ADR lays out the problem and options for a future call. The options below are open and require discussion and consensus before implementation.

---

## Alternatives Considered

### Option 1: Keep the Boolean Flags As-Is (Status Quo)

Accept that assisted and added-weight nuance, bodyweight-as-load analytics, and relative-strength views are beyond the current scope. The existing `bodyweight_base` and `allows_added_weight` flags on the definition, plus the `bodyweight_only` flag on the log, cover the happy path (basic bodyweight + simple added weight). Advanced cases are deferred or approximated.

**Pros:**
- No schema changes required.
- No new database tables or columns.
- Minimum complexity and maintenance burden.

**Cons:**
- Cannot record or analyze assisted bodyweight work (band-assisted pull-ups, machine-assisted dips).
- Volume and PR calculations for weighted bodyweight exercises are incomplete (ignore the user's bodyweight as a load component).
- Progress tracking queries cannot normalize across users or time (bodyweight is not captured at set time).
- Relative-strength analytics (lifts as a percentage of bodyweight) require manual computation outside the app.
- The heuristic in Phase 2 seeding (`bodyweight_base` inferred from equipment type) may be inaccurate; no easy correction path without a broader bodyweight model.

### Option 2: Signed Load on the Existing Load Metric

Extend `log_load_metrics` to allow added weight to be positive and assistance (band/machine) to be negative on a single column. A weighted pull-up would store `+25` (lbs added); a band-assisted pull-up would store `-40` (lbs assistance). This collapses added weight and assistance into a single signed-load dimension.

**Pros:**
- Expressible in the existing schema with no new tables.
- Adds nuance for assisted work without expanding the metric tables.
- Queries can sum absolute load across entries (though negative values need care in interpretation).

**Cons:**
- Semantics of a negative "weight" are counterintuitive. Code and queries must explicitly handle the sign; this is error-prone without strong documentation.
- Does not capture the user's bodyweight at set time, so true load = bodyweight + signed_load still cannot be computed for volume/PR analytics.
- Mixing negative and positive on the same column violates the "no magic values" principle (though less egregiously than 0/"Body"). Reviewers and future maintainers may misinterpret a negative value as a data entry error.
- Progress queries would need conditional logic to interpret negative loads correctly.

### Option 3: Capture Bodyweight as a First-Class Log Input

Record the user's bodyweight at workout (or set) time so that true load can be computed as bodyweight +/- added/assist. This implies a new column on `workout_entries` or a new metric table `log_bodyweight_snapshots`, plus a decision about granularity (per-workout vs. per-set) and source (profile snapshot at workout time vs. manual entry per set).

**Pros:**
- Enables true-load computation for volume and PR analytics (bodyweight + added - assistance).
- Supports relative-strength views (lifts as % of bodyweight at set time).
- Bodyweight-as-time-series becomes queryable for progress tracking.
- Avoids magic values; bodyweight is a first-class numeric field.

**Cons:**
- Requires a migration and new column(s) on `workout_entries` or a new metric table.
- Decision needed: capture at workout level (simpler, assumes consistent bodyweight across a workout) or per-set (more accurate, more granular).
- Decision needed: automatic (snapshot user's profile weight at workout time) or manual (user enters bodyweight per workout/set). Manual entry adds friction; automatic creates stale data if the user's weight changes mid-phase.
- If granularity is per-set, adds a column to every workout entry even for exercises that don't need it (pure luck if this is a performance win).
- Phase 9 progress queries become more complex; they now need to handle bodyweight as a load variable in calculations.

### Option 4: A Dedicated Bodyweight-Load Metric / Sub-Model

Model bodyweight loading as its own composable metric dimension (separate from `log_load_metrics`), with columns for added load, assistance load, and bodyweight-at-time. For example, a `log_bodyweight_metrics` table carries `target_bodyweight`, `actual_bodyweight`, `target_added_load`, `actual_added_load`, `target_assistance`, `actual_assistance`. This is the most expressive option.

**Pros:**
- Fully expressive: added weight, assistance, and bodyweight are first-class, named columns.
- Aligns with composable-metrics philosophy (ADR-006): bodyweight loading is one orthogonal metric dimension.
- Separate metric table keeps bodyweight logic isolated and testable.
- Phase 9 progress queries can directly access true load components without computation.

**Cons:**
- Requires a new table and a migration.
- Adds schema surface area: 6+ columns on a new table, applied only to resistance exercises with bodyweight.
- Overkill for exercises that don't use bodyweight; the metric attaches to an entry even when unused.
- Most schema cost of all options.
- Requires a clear decision about which exercises attach this metric (only `bodyweight_base=true`? or all resistance exercises?).

---

## Consequences

### Positive
- This ADR names the problem space explicitly, preventing ad-hoc solutions during Phase 9 work.
- Options are laid out so the choice can be made with full context rather than discovered during implementation.

### Negative
- Phase 2 seeding (bodyweight_base heuristics) will not be perfect; it relies on the current thin model.
- Phase 9 (progress tracking) work will be blocked until this decision is made; the choice directly affects how PRs and volume are calculated.

### New Decisions Required
- Which option to pursue: status quo, signed load, bodyweight capture, or dedicated metric.
- If Option 3 or 4: granularity (per-workout vs. per-set) and source (automatic vs. manual).
- If Option 3: whether to snapshot bodyweight on `workout_entries` or create a separate `log_bodyweight_snapshots` table.
- If Option 4: which exercises attach the metric and how to validate it in the application layer.
- Either way: how Phase 9 progress queries adapt to the chosen model.

---

## Influences

- ADR-003 (exercise definitions, CTI) established `bodyweight_base` and `allows_added_weight` as boolean flags, insufficient for advanced cases.
- ADR-006 (composable metrics) introduced the multi-table metric pattern; a new metric table is a natural fit if option 4 is chosen.
- ADR-005 (weight unit, raw numbers no conversion) constrains the data type (decimal, not canonical unit), but the choice of how to store bodyweight-related values depends on this ADR's outcome.
- Phase 2 seeding work (free-exercise-db integration) creates heuristic values for `bodyweight_base` based on equipment type. These will be preserved regardless of which option is chosen, but a robust model will enable future corrections.
- User feedback and design intent: Justin flagged that bodyweight handling "feels almost like its own thing" and should be explored separately rather than decided inline during seed-data work.

---

## Related Decisions

- **ADR-003** (exercise definitions): defines `bodyweight_base` and `allows_added_weight` flags; this decision may extend or replace them.
- **ADR-005** (weight unit preference): constrains the data type of any weight-related columns; raw numbers in user's configured unit.
- **ADR-006** (composable metrics): if option 4 is chosen, a new metric table fits the composable pattern.
- **ADR-002** (deletion): no change expected, but deletion impact should be verified if schema is extended.
- **Phase 9 (Progress Tracking)**: future ADRs for PR detection, volume calculation, and relative-strength analytics will depend on this decision.
- **Phase 2 Seeding**: currently uses heuristics for `bodyweight_base` from free-exercise-db equipment type; the choice here does not change Phase 2 but informs any future corrections to seeded data.

---

## Participants

- Justin Christenson (decision maker)

## Review Date

Before Phase 9 (Progress Tracking) implementation begins. This decision is load-bearing for PR and volume analytics and should be finalized before that work is scoped.
