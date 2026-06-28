# ADR-012: Distance Unit Preference

## Status
Superseded by [ADR-013](adr-013-measurement-system-preference.md)

## Date
2026-06-28

## Deciders
- Justin Christenson (Decision maker and application owner)

---

## Context

Physistrong is a self-hosted fitness tracking app used internationally.
Users need to work in their preferred distance unit (miles or kilometers).
Distance is tracked for running, cycling, and other distance-based exercises,
and this preference is essential for usability worldwide.

---

## Decision

Distance and speed values are stored as raw numbers in whatever unit
the user has configured. No canonical unit, no server-side conversion.

### User profile

The user's unit preference (`distance_unit`: 'miles' or 'kilometers') is stored on
their user record. This is a **required field at registration** (the
create user screen forces the user to pick). The setting is also exposed
in profile settings for later changes.

### Storage

Distance/speed columns store plain numbers. The number means whatever
the user's configured unit says it means. A value of 5 for a user
configured to miles means 5 miles. A value of 8 for a user configured to
kilometers means 8 kilometers.

### API responses

API resources include the user's configured unit as a separate field
alongside distance/speed values where they apply. Example:

```json
{
  "actual_distance": 5,
  "distance_unit": "miles"
}
```

### Unit switching

Not supported at this time. A user should know their unit of measurement.
If a user changes their preference in profile settings, historical values
are not converted. This may be revisited if it becomes a real need.

---

## Migration Structure

```sql
-- Add to users table:
distance_unit (enum: 'miles', 'kilometers', NOT NULL)
```

No changes to metric tables. Distance columns remain plain decimals.

---

## Consequences

### Positive
- Simple implementation: no conversion logic, no canonical unit
- No precision loss from conversion math
- API contract is clear: number + unit in the response
- Required at registration prevents ambiguous data
- Mirrors weight_unit approach for consistency

### Negative
- Switching units does not convert historical data
- Multi-user households where members use different units will see each
  other's data in the other's unit system (if data sharing is ever added)

---

## Related Decisions

- **ADR-005** (weight unit preference) follows the same pattern
- **ADR-003** (exercise definitions) no distance columns on definitions
- **ADR-006** (composable metrics) `log_distance_metrics.actual_distance` and
  `target_distance` are raw numbers interpreted by the user's unit preference

---

## Participants

- Justin Christenson (decision maker)

## Review Date

Review if users report needing unit conversion for historical data after
changing their preference.
