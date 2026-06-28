# ADR-008: API Format and Versioning

## Status
Accepted

## Date
2026-06-27

## Deciders
- Justin Christenson (Decision maker and application owner)

---

## Context

Physistrong serves two clients: a React SPA (MVP) and a future native mobile
app (Android/React Native). Both clients consume the same API. The legacy app
used a custom JSON:API variant with typed resources and relationship links.
The new build needs to decide on API response format and versioning strategy.

---

## Decision

### Format: Standard REST

Plain JSON responses. Not JSON:API (too much ceremony for a single-client
app at launch). Not Inertia.js (doesn't produce a standalone API, and the
mobile app needs one).

Resource responses return flat JSON objects with the entity's attributes.
Collection responses include pagination metadata. Error responses use a
consistent structure with status code and message.

### Versioning: /v1/ prefix

All API routes are prefixed with `/v1/`. Required even for a self-hosted app
because:
- The mobile app and web app may be on different release cycles
- Breaking API changes can be introduced under `/v2/` without disrupting
  existing clients
- Standard practice that costs nothing to implement upfront

### Auth endpoints

Auth routes (login, logout, register, password reset) are under the
versioned prefix alongside resource routes. No separate auth namespace.

---

## Consequences

### Positive
- Simple response format reduces client-side parsing complexity
- One API serves both web and mobile clients
- Versioning enables future breaking changes without disruption
- No JSON:API library dependency on the frontend

### Negative
- No built-in relationship links or typed resources (must be handled
  ad-hoc if needed for HATEOAS)
- Versioning adds a prefix to every route definition

---

## Related Decisions

- **ADR-001** (auth): Passport + JWT, auth endpoints under /v1/
- **ADR-005** (weight unit): API responses include weight_unit field
  alongside weight values
- **ADR-006** (composable metrics): query patterns assume standard REST
  responses

---

## Participants

- Justin Christenson (decision maker)

## Review Date

Review when the mobile app launches to evaluate whether the API format
serves both clients well.
