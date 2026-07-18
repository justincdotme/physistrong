# ADR-014: Unitless Distance Values

## Status
Accepted

## Supersedes
The distance_unit clauses of:
- [ADR-003](adr-003-exercise-type-set-data-model.md) (`exercise_distance.distance_unit` column)
- [ADR-006](adr-006-composable-metrics-performance-log.md) (`log_distance_metrics.distance_unit` column)
- [ADR-013](adr-013-measurement-system-preference.md) ("remains unchanged" notes for both columns)

## Date
2026-07-03

## Deciders
- Justin Christenson (Decision maker and application owner)

---

## Context

ADR-013 made the user's `measurement_system` the single unit authority:
raw numbers in the database, display labels resolved by
`MeasurementLabelService` (since removed; see the amendment below), no
conversion. Weight already worked this way. Distance did not: a hand-rolled `DistanceUnit` enum
(meters/kilometers/miles/yards) drove two per-record columns,
`exercise_distance.distance_unit` and
`log_distance_metrics.distance_unit`, a parallel unit vocabulary
unrelated to the php-unit-conversion library behind the label service.

The per-exercise column had also decayed: migration `2026_06_29_000001`
downgraded it from a constrained enum to a nullable unconstrained
string, the PS-80 UI work removed the selector that populated it, and
the Store/Update exercise requests drifted (nullable vs required). The
2026-07-02 backend review surfaced this; the owner decided
to remove the per-record unit concept rather than re-harden it (PS-95).

---

## Decision

Distance values are unitless numbers interpreted through the user's
measurement system, exactly like weight.

- Both `distance_unit` columns are dropped. Existing column data was
  discarded without conversion, consistent with the no-conversion rule:
  numbers are reinterpreted in the user's system unit.
- The `DistanceUnit` enum is deleted. No request accepts, and no
  response carries, a per-record distance unit.
- `MeasurementLabelService` (unchanged) resolves display labels from
  `measurement_system`: imperial shows mi, metric shows km. The SPA
  derives labels the same way (`unitLabel(system, 'distance')`).

> Amended 2026-07-17 (PS-156): `MeasurementLabelService` and the
> `php-unit-conversion/php-unit-conversion` dependency were removed.
> `resources/js/lib/units.ts` (`unitLabel()`) is now the sole label
> resolver, in the SPA rather than the API, covering weight and
> distance. Distance values stay raw numbers reinterpreted by
> `measurement_system`, unconverted, exactly as decided above.

Out of scope: `log_distance_metrics.lap_count` / `stroke_count` and all
other distance metric columns remain.

---

## Consequences

### Positive
- One unit authority; distance and weight now follow the same rule
- No unit vocabulary drift between schema, enum, and label service
- Simpler requests, models, seed data, and SPA payloads

### Negative
- A user who switches measurement system reinterprets historical
  distances (5 mi becomes 5 km); accepted, identical to weight behavior
- Sub-mile/kilometer sports (400 m track work) must be logged as
  fractions of the system unit

---

## Related Decisions

- **ADR-013** (measurement system preference) provides the authority
  this ADR extends to distance
- **ADR-003** / **ADR-006** define the tables the columns were dropped
  from

---

## Participants

- Justin Christenson (decision maker)
