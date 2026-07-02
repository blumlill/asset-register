# Project-wide coding standards

## Rule Priority

These rules override default framework conventions, Laravel defaults, and any suggestions from generated code unless explicitly stated otherwise.

## Enforcement level

Treat this document as mandatory engineering constraints. Do not suggest alternative architectures unless explicitly asked.

## Decision rule

When multiple valid implementations exist:
1. Prefer simplicity over abstraction
2. Prefer Laravel-native features over custom patterns
3. Prefer consistency with existing codebase over theoretical purity

## Stack & Environment

- **PHP 8.5**, **Laravel 13**, **MySQL**, **Docker / docker-compose**
- No UI — pure JSON REST API
- Swagger/OpenAPI docs served on a public endpoint protected by HTTP Basic Auth
- PSR-4 autoloading, PSR-12 code style — enforce on every file

---

## Architecture

The codebase uses a **Hexagonal / Clean Architecture** approach inside Laravel's `app/` directory. Four layers; dependency direction is always inward toward `Business/`.

```
app/
  Business/                        # Pure domain — ZERO Illuminate\* imports allowed
    {Context}/
      Domain/
        Entities/                  # Rich domain entities
        ValueObjects/              # Immutable VOs — use for domain concepts with format/invariant rules
        Aggregates/                # Aggregate roots
        Exceptions/                # Domain-specific exceptions
      Contracts/                   # PHP interfaces: repository ports, service ports
      Services/                    # Domain/application services (pure PHP)
      DTOs/                        # readonly PHP classes — bridge to presentation layer
      Events/                      # Plain PHP domain event classes (no ShouldQueue here) — create only when events are actively dispatched and listened to

  Repository/                      # Driven adapters — implement Business\Contracts interfaces
    Eloquent/
      Models/                      # Eloquent models live HERE ONLY (DB mappers, not domain entities)
      Repositories/                # Eloquent implementations of Business\Contracts repository interfaces
      Mappers/                     # Translate Eloquent model ↔ Domain entity; never expose models outside this layer
    External/                      # 3rd-party API clients and SDKs

  Http/                            # Laravel-native presentation layer (driving adapter)
    Controllers/                   # Thin: validate → call Business\Service → return Resource
    Requests/                      # FormRequest subclasses — input validation only
    Resources/                     # API Resources — JSON response shaping
    Middleware/                    # Auth, rate limiting, LogApiRequest

  Providers/                       # Laravel DI wiring — bind Business\Contracts → Repository implementations
  Exceptions/                      # optional — only if exception renderers grow too large for bootstrap/app.php

  Core/
    Logging/                       # ApiLog Eloquent model, log formatters, field masking
    Support/                       # Base classes, shared traits, helpers
```

### Dependency rules

| Layer          | May import from                        | Must NOT import from                    |
|----------------|----------------------------------------|-----------------------------------------|
| `Business/`    | nothing outside `Business/`            | `Illuminate\*`, `Repository\*`, `Http\*` |
| `Repository/`  | `Business\Contracts\*`, `Illuminate\*` | `Http\*`                                |
| `Http/`        | `Business\*`, `Illuminate\*`           | `Repository\*` directly                 |
| `Providers/`   | all layers (wiring only)               | —                                       |
| `Core/`        | `Illuminate\*`                         | `Business\*`, `Repository\*`            |

- Controllers call Business services or dispatch domain events — never touch Eloquent directly
- Eloquent models never leave `Repository/` — mappers translate at the boundary
- Constructor injection everywhere — no `new ConcreteClass()` outside `Providers/`

---

## CRUD REST API Conventions

- Route pattern: `GET /api/v1/{resource}`, `POST`, `PUT /api/v1/{resource}/{id}`, `DELETE`
- Always return responses through a `JsonResource` or `ResourceCollection`
- HTTP status codes must be semantically correct (201 for creation, 204 for deletion, 422 for validation)
- All endpoints documented with `#[OA\...]` PHP 8 attributes (`zircote/swagger-php`)
- Controller action methods: max ~15 lines

---

## Swagger / OpenAPI

- Docs endpoint: `GET /api/docs` (Swagger UI) — protected by HTTP Basic Auth middleware; credentials in `.env`, never hardcoded
- Every controller action and every DTO/Resource property must carry `#[OA\...]` annotations
- Docs regenerated at build/CI time, not at runtime

---

## Database & Migrations

- Every schema change via a **migration** — never modify an existing migration
- Foreign key constraints defined in migrations, not just at the Eloquent level
- Use `foreignId()` / `foreignUuid()` helpers; prefer UUIDs for all public-facing IDs
- Seeders provide deterministic fixture data; model factories are test-only

---

## Request/Response Logging

- Every inbound request and outbound response is persisted to `api_logs` table
- Schema: `id`, `method`, `path`, `request_headers` (JSON), `request_body` (JSON), `response_status`, `response_body` (JSON), `duration_ms`, `created_at`
- Implemented exclusively in `Http\Middleware\LogApiRequest` — `handle()` captures start time, `terminate()` calculates duration and persists after the response is flushed; client never waits
- Mask sensitive fields (`Authorization`, `password`, `token`, `secret`) before persisting
- Logging failure must never propagate — catch internally, fall back to Laravel log channel

---

## Unified Error Handling

- Single entry point: `bootstrap/app.php` via `withExceptions()::renderable()` — no try/catch in controllers for expected domain exceptions
- Error response envelope:
  ```json
  { "error": { "code": "SCREAMING_SNAKE_CASE", "message": "...", "details": {} } }
  ```
- All custom exception classes carry a `public readonly string $errorCode` property
- Domain exceptions live in `Business\{Context}\Domain\Exceptions\`
- Validation errors (422) rendered with per-field detail under `details`
- Stack traces and internal paths never exposed outside `APP_DEBUG=true`

---

## Testing — TDD-ish Workflow

### Philosophy
- Write the test before (or immediately alongside) the implementation
- Red → Green → Refactor for all business logic

### Test layout
```
tests/
  Unit/
    Business/        # Domain services, entities, VOs — no DB, no HTTP, pure mocks
  Feature/
    Api/             # Full HTTP → DB → JSON response integration tests
  Integration/
    Repository/      # Repository implementations against a test database
```

### Rules
- Unit tests: zero database access, zero HTTP — mock all `Business\Contracts` interfaces
- Feature tests: use `RefreshDatabase`; assert HTTP status, response JSON structure, and DB state
- Never share mutable state between tests
- Naming: `{Subject}Test` for units, `{Resource}ApiTest` for feature tests
- Coverage target: ≥ 80% in `Business/` layer; exclude plain getters/setters on VOs/entities and framework method overrides (e.g. `rules()`, `messages()` on FormRequests)

---

## Dockerization

- `docker-compose.yml` at project root: `app` (PHP-FPM), `webserver` (Nginx), `db` (MySQL 8)
- Multi-stage `Dockerfile`: builder stage runs `composer install --no-dev`; runtime stage is a slim image
- All config via `.env` — no hardcoded ports, credentials, or hostnames in any committed file
- `docker-compose.override.yml` for local dev (Xdebug, volume mounts)
- `db` service has a health check; `app` uses `depends_on: condition: service_healthy`

---

## Code Style — PSR-12 + Project Additions

- Every PHP file: `<?php declare(strict_types=1);`
- DTOs and Value Objects are `readonly` classes
- Full type hints everywhere — `mixed` requires an explanatory comment
- Aim for: method body ≤ 20 lines; class body ≤ 200 lines — extract otherwise
- No `static` methods in `Business/` except named constructors on DTOs/VOs
- `use` aliases only when disambiguating two same-named classes; imports grouped: built-ins → framework → project

---

## Naming, Comments & Clean Code

- **No docblocks, no inline comments** unless the code cannot express the intent on its own — if a comment is needed to explain *what* the code does, rename or refactor instead
- Method and variable names must be self-explanatory: `assignAssetToContract()` not `doAction()`, `$serialNumber` not `$sn`
- Class and method **scope** is part of the design — use `private`/`protected` by default, only widen to `public` when required by an interface or the calling layer
- **SOLID, DRY, OOP, composition over inheritance** — single responsibility, depend on abstractions, no duplication, no standalone helper functions, inject collaborators; inherit only for true is-a relationships; extract to services, VO methods, or traits
- Prefer **explicit over clever**: no magic numbers, no repeated string literals (use constants or enums), no complex one-liners that obscure intent

---

## Loose Coupling

- Depend on interfaces from `Business\Contracts`, not concrete classes
- Every class must be instantiable and unit-testable without booting the Laravel container
- No `new ConcreteClass()` — use constructor injection
- `Business/` classes must not contain `Illuminate\*` imports
- Eloquent models must not be passed beyond the `Repository\Eloquent\Mappers` boundary
