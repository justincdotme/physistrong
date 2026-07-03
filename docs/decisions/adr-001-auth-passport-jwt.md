# ADR-0001: Use Laravel Passport with OAuth2 + JWT for Authentication

## Status

Accepted (amended 2026-07-02 with PS-90 implementation notes)

## Date

2026-06-27

## Deciders

- Justin Christenson (Decision maker)

---

## Context

Physistrong is being rebuilt as a self-hosted fitness tracking app on Laravel 13, MySQL, and React. The system will serve both a React web frontend and a future Android/React Native mobile app. Both clients require stateless authentication with payload claims to enable horizontal scaling and seamless token portability across devices.

The legacy application used a development branch of tymon/jwt-auth, which lacked proper OAuth2 client management and refresh token handling. The new architecture must support:

- Token revocation without database lookups per request
- Portable tokens suitable for mobile clients
- Payload claims for authorization decisions
- Separate delivery mechanisms optimized for browser and mobile security models

---

## Decision

We will use Laravel Passport with OAuth2 + JWT for authentication.

**Implementation details:**

- JWTs will include a JTI (JWT ID) claim for token identity
- Web clients receive JWT in an HTTP-only cookie (standard browser security practice)
- Mobile clients receive JWT in the response body for secure device storage, sent via Authorization header on subsequent requests
- Logout invalidates tokens via a Redis-backed JTI blacklist where each entry's TTL equals the remaining lifetime of the invalidated token, so entries auto-expire without filling up Redis

---

## Implementation Notes (2026-07-02, PS-90)

The JTI blacklist is implemented behind Laravel's cache layer rather
than raw Redis commands:

- `App\Services\TokenBlacklist` writes `auth:revoked-jti:{jti}`
  entries through the default cache store with the entry expiry set
  to the token's `expires_at`. The deployed stack sets
  `CACHE_STORE=redis` (cache connection, Redis database 1), so
  entries land in Redis under the framework cache prefix and expire
  with the token. The test suite swaps in the array store, keeping
  the suite free of a Redis dependency.
- `App\Http\Middleware\RejectBlacklistedTokens` runs after `auth:api`
  on the protected route group and rejects blacklisted JTIs with 401.
- Logout keeps Passport's database revocation (`revoked` flag) as a
  second layer, so Passport's per-request database token lookup still
  runs. The "token revocation without database lookups per request"
  property above is a design goal, not yet the runtime behavior.
  Removing the lookup is deferred until blacklist state can be
  rebuilt from the database after a Redis flush (PS-124).
- A Redis flush does not resurrect logged-out tokens today because
  the database `revoked` flag still rejects them.

Cookie-based delivery for web clients remains open under PS-91.

---

## Alternatives Considered

### Option A: Laravel Sanctum

Cookie-based SPA authentication plus opaque API tokens. Sanctum uses Laravel's built-in session system for web clients and opaque tokens for APIs.

**Rejected because**:
- Does not support JWT; relies on opaque tokens that require a database lookup per request
- Does not include payload claims, limiting authorization flexibility
- Not suitable for mobile clients that need portable, stateless tokens
- Increases latency due to database round-trips on every authenticated request

### Option B: Standalone JWT package (e.g., firebase/php-jwt)

A lightweight JWT library without additional OAuth2 infrastructure.

**Rejected because**:
- Lacks OAuth2 client management (multiple app clients, credential flows, scopes)
- No built-in refresh token handling or revocation mechanism
- No scope-based access control out of the box
- Would require manual implementation of these features, increasing development time and error surface
- Does not integrate with Laravel's authentication guard system, creating custom maintenance burden

---

## Pros

- Stateless authentication reduces session storage overhead and enables horizontal scaling without sticky sessions
- JTI-based blacklist provides efficient token revocation without requiring a database lookup per request
- Redis-backed TTL management prevents unbounded blacklist growth; entries automatically expire matching token lifetime
- Dual delivery mechanism (HTTP-only cookie for web, response body for mobile) optimizes security for each client type
- Adds OAuth2 client management and scope-based access control out of the box, supporting future mobile clients and third-party integrations

---

## Cons

- Requires Redis infrastructure for JTI blacklist management, adding an operational dependency
- JWT tokens are larger than opaque tokens, slightly increasing request/response payload size
- No automatic client logout across all devices; logout on one device does not affect other active tokens without explicit client-side action

---

## Consequences

### Positive

- Token payload can carry authorization claims (roles, permissions, user metadata), enabling faster authorization decisions without additional database queries
- Mobile clients can securely store JWTs on device and reuse them across app sessions without server-side session state
- API can be scaled horizontally without sticky sessions or distributed session caching
- Future Android/React Native client and third-party OAuth2 clients can reuse the same authentication infrastructure

### Negative

- Redis becomes a required dependency; system cannot operate if Redis is unavailable
- Token revocation is eventually consistent; blacklisted tokens may remain briefly in flight between servers
- Logout is explicit (client calls the logout endpoint); users cannot be logged out server-side without a token invalidation check

### New Decisions Required

- Implement secure Redis connection pooling and failover strategy to keep the blacklist available
- Decide on HTTP-only cookie SameSite and Secure flags for web clients to prevent CSRF and XSS attacks
- Define JWT expiration times (access token TTL and refresh token rotation policy)
- Determine scope definitions and their mapping to application permissions

---

## Influences

- Physistrong is a self-hosted app requiring stateless authentication for horizontal scaling
- Mobile app development (future Android client) necessitates portable tokens suitable for device storage
- Legacy tymon/jwt-auth approach was incomplete; OAuth2 client management was missing
- Redis is already in the tech stack for caching, making it a natural fit for blacklist storage

---

## Related Decisions

- Future ADR needed: JWT expiration and refresh token rotation strategy
- Future ADR needed: Redis failover and high-availability configuration for the blacklist

---

## Review Date

Review when the mobile (Android/React Native) client launches in production to validate that JWT payload claims meet mobile authorization requirements and that blacklist performance remains acceptable at production scale.
