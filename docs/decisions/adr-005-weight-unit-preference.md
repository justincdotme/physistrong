# ADR-005: Weight Unit Preference

## Status
Accepted

## Date
2026-06-27

## Deciders
- Justin Christenson (Decision maker and application owner)

---

## Context

Physistrong is a self-hosted fitness tracking app used internationally.
Users need to work in their preferred weight unit (KG or Lb). This is an
MVP requirement so the app is usable worldwide.

---

## Decision

Weight and resistance values are stored as raw numbers in whatever unit
the user has configured. No canonical unit, no server-side conversion.

### User profile

The user's unit preference (`weight_unit`: 'kg' or 'lb') is stored on
their user record. This is a **required field at registration** (the
create user screen forces the user to pick). The setting is also exposed
in profile settings for later changes.

### Storage

Weight/resistance columns store plain numbers. The number means whatever
the user's configured unit says it means. A value of 135 for a user
configured to Lb means 135 Lb. A value of 60 for a user configured to KG
means 60 KG.

### API responses

API resources include the user's configured unit as a separate field
alongside weight/resistance values where they apply. Example:

```json
{
  "actual_weight": 135,
  "weight_unit": "lb"
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
weight_unit (enum: 'kg', 'lb', NOT NULL)
```

No changes to metric tables. Weight columns remain plain decimals.

---

## Consequences

### Positive
- Simple implementation: no conversion logic, no canonical unit
- No precision loss from conversion math
- API contract is clear: number + unit in the response
- Required at registration prevents ambiguous data

### Negative
- Switching units does not convert historical data
- Multi-user households where members use different units will see each
  other's data in the other's unit system (if data sharing is ever added)

---

## Related Decisions

- **ADR-003** (exercise definitions) no weight columns on definitions
- **ADR-006** (composable metrics) `log_load_metrics.actual_weight` and
  `target_weight` are raw numbers interpreted by the user's unit preference

---

## Participants

- Justin Christenson (decision maker)

## Review Date

Review if users report needing unit conversion for historical data after
changing their preference.
