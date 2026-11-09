# ADR-013: Measurement System Preference

## Status
Accepted

Both per-record `distance_unit` columns (`exercise_distance` and
`log_distance_metrics`) are superseded by
[ADR-014](adr-014-unitless-distance-values.md); they were dropped
on 2026-07-03.

## Supersedes
- [ADR-005](adr-005-weight-unit-preference.md) (weight unit preference)
- [ADR-012](adr-012-distance-unit-preference.md) (distance unit preference)

## Date
2026-06-28

## Deciders
- Justin Christenson (Decision maker and application owner)

---

## Context

The app originally planned separate per-dimension unit preferences
(`weight_unit` for kg/lb, `distance_unit` for miles/km). This creates UX
friction: two selectors at registration instead of one. It adds complexity
for each new measurement dimension (e.g. adding elevation requires another
user preference column). This diverges from standard fitness app practice
where users select a single measurement system that applies globally.

---

## Decision

Replace per-dimension unit preferences with a single `measurement_system`
enum (`imperial` | `metric`) on the user record. This preference is
required at registration and editable in profile settings.

A `MeasurementLabelService` resolves `(measurement_system, dimension)`
to a unit label using the `php-unit-conversion/php-unit-conversion`
library. The service is the single point of truth for all unit label
resolution.

> Amended 2026-07-17 (PS-156): `MeasurementLabelService` and the
> `php-unit-conversion/php-unit-conversion` dependency were removed.
> `resources/js/lib/units.ts` (`unitLabel()`) is the current label
> resolver, covering weight and distance. Speed in the table below
> was dead code and was removed with the service. Values remain raw
> numbers, unconverted, per the decision above.

### Measurement dimension table

Derived from a codebase audit of exercise types (ADR-003), composable
metrics (ADR-006), and cardio settings:

| Dimension | Imperial | Metric | Used by |
|-----------|----------|--------|---------|
| Weight | lb | kg | `log_load_metrics` (target/actual weight) |
| Distance | mi | km | `log_distance_metrics` (target/actual distance) |
| Speed | mph | km/h | `log_cardio_settings` (speed) |

Dimensions with fixed units (no system preference needed): duration
(seconds), reps (count), rounds (count), cadence (RPM), heart rate (BPM),
incline (percentage), resistance level (unitless).

> Amended 2026-08-23 (PS-164): duration keeps its fixed unit and is
> still stored and transmitted as integer seconds, but it is never shown or
> typed as a raw second count. `resources/js/lib/duration.ts` renders it as
> `mm:ss`, widening to `hh:mm:ss` past an hour, and `DurationInput` accepts
> it as three `hh`/`mm`/`ss` segments that normalize overflow on blur.

### Storage

Values are stored as raw numbers. No canonical unit, no server-side
conversion. The number means whatever the user's configured measurement
system says it means.

### API responses

API resources include the user's measurement_system as context. Individual
metric values do not carry inline unit fields at the user-preference level.

### Unit switching

Not supported in the MVP. Changing the measurement system changes future
labels but does not convert historical data. The `php-unit-conversion`
library supports conversion via its `->to()` method for future
implementation.

### Per-exercise distance_unit

> Superseded by [ADR-014](adr-014-unitless-distance-values.md): both
> per-record distance_unit columns were dropped on 2026-07-03.

The `exercise_distance.distance_unit` column (ADR-003) is a per-exercise
definition attribute (meters/kilometers/miles/yards), not a user preference.
It remains unchanged. Similarly, `log_distance_metrics.distance_unit` is a
per-entry attribute that records what unit a specific value was logged in.

---

## Migration Structure

```sql
-- Add to users table:
measurement_system (enum: 'imperial', 'metric', NOT NULL, DEFAULT 'imperial')
```

> Amended 2026-07-17 (PS-156): the column is a plain string, not a
> database-level enum, and carries no default.
> `App\Enums\MeasurementSystem` validates and casts it at the
> application layer; the field is required at registration.

No `weight_unit` or `distance_unit` columns on the users table.

---

## Consequences

### Positive
- Single selector at registration instead of two
- Adding a new measurement dimension (e.g. elevation) requires one case
  in the service, not a new user preference column
- Standard pattern used by every major fitness app
- `php-unit-conversion` library handles symbol resolution and future
  conversion
- Consistent labels across all display surfaces

### Negative
- Users cannot mix systems (e.g. weight in kg but distance in miles).
  This is an intentional trade for simplicity and matches fitness app
  conventions.

---

## Related Decisions

- **ADR-003** (exercise definitions) `exercise_distance.distance_unit`
  dropped (amended 2026-07-17, PS-156), see [ADR-014](adr-014-unitless-distance-values.md)
- **ADR-006** (composable metrics) `log_distance_metrics.distance_unit`
  dropped (amended 2026-07-17, PS-156), see [ADR-014](adr-014-unitless-distance-values.md)
- **ADR-008** (API format) API responses include measurement_system on
  user resource

---

## Participants

- Justin Christenson (decision maker)

## Review Date

Review if users report needing mixed measurement systems or unit
conversion for historical data.
