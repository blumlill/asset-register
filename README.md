# Asset Register API

A JSON REST API for managing physical assets and the contracts they are assigned to. Built with Laravel 13, PHP 8.5, 
and MySQL 8, following a Hexagonal (Clean) Architecture that keeps domain logic free of framework dependencies.

Full architecture details, domain model, and sequence diagrams are in [`docs/`](docs/) and [`.claude/CLAUDE.md`](.claude/CLAUDE.md)

---

## Local environment

**First-time setup** — builds images, starts containers, runs migrations, and seeds the database:

```bash
make build
```

**IDE support** — installs all dependencies (including dev) into a host-side `vendor/` for IDE indexing. No local PHP 
or Composer required.

```bash
make vendor
```

**Subsequent starts:**

```bash
make up      # start containers and apply pending migrations
make down    # stop containers and remove volumes
make restart # restart containers
make logs    # tail container logs
make shell   # open a bash shell in the app container
```

**Database utilities:**

```bash
make migrate # run pending migrations
make seed    # run seeders
make fresh   # drop all tables, re-migrate, and re-seed
```

---

## API Documentation

Interactive Swagger UI is available at [`http://localhost:8080/api/docs`](http://localhost:8080/api/docs) after `make build`.

The endpoint is protected by HTTP Basic Auth. Default credentials are set in `.env` (`SWAGGER_USER` / `SWAGGER_PASSWORD`):

| Field    | Default   |
|----------|-----------|
| User     | `swagger` |
| Password | `secret`  |

---

## Tests

The test suite runs in an isolated Docker environment that is spun up and torn down automatically — local dev containers
are unaffected:

```bash
make test
```

---

## Linting & static analysis

`make lint` runs two tools in sequence. Both must pass clean.

```bash
make lint
```

| Tool                                              | Purpose                                                          |
|---------------------------------------------------|------------------------------------------------------------------|
| [Laravel Pint](https://laravel.com/docs/pint)     | Fixes code style to the Laravel preset                           |
|                                                   | (PSR-12 + Laravel conventions)                                   |
| ................................................. | ................................................................ |
| [PHPStan](https://phpstan.org) via                | Static analysis at level 6, Laravel-aware                        |
| [Larastan](https://github.com/larastan/larastan)  |                                                                  |

Requires a host-side `vendor/` directory — run `make vendor` first if you haven't already.

---

## Architecture

```
app/
  Business/       # Pure domain — no Illuminate imports
  Repository/     # Eloquent adapters implementing Business contracts
  Http/           # Controllers, FormRequests, API Resources
  Core/           # Logging middleware, shared infrastructure
```

Dependency direction is always inward: `Http` → `Business` ← `Repository`. Eloquent models never cross the 
`Repository/Eloquent/Mappers` boundary.

- [`docs/architecture.puml`](docs/architecture.puml) — component diagram
- [`docs/domain-model.puml`](docs/domain-model.puml) — domain model
- [`docs/sequences/`](docs/sequences/) — per-operation sequence diagrams

---

## Tech choices

| Technology               | Reason                                                                     |
|--------------------------|----------------------------------------------------------------------------|
| Laravel 13               | Mature HTTP, DI, migration, and testing primitives — reduces boilerplate   |
|                          | without leaking into domain logic                                          |
| ........................ | .......................................................................... |
| PHP 8.5                  | `readonly` classes, enums, and fibers make value objects and DTOs          |
|                          | first-class citizens                                                       |
| ........................ | .......................................................................... |
| MySQL 8                  | Window functions and JSON columns for log querying; wide hosting support   |
| ........................ | .......................................................................... |
| Docker / Compose         | Reproducible dev and test environments; multi-stage build keeps the        |
|                          | production image lean                                                      |
| ........................ | .......................................................................... |
| zircote/swagger-php      | Attribute-based OpenAPI annotations keep docs co-located with the code     |
|                          | code they describe                                                         |
| ........................ | .......................................................................... |
| Laravel Pint + PHPStan   | Automated formatting and static analysis — enforces PSR-12 and type        |
|                          | correctness on every run                                                   |

---

## Future improvements

- **Audit trail via domain events** — the domain model has natural event boundaries; introducing domain events and 
persisting them would give a full history of changes without relying on soft deletes or log scraping.
- **Async log writes** — `LogApiRequest::terminate()` already runs after the response is flushed; moving persistence to 
a queued job would eliminate any remaining write latency on high-traffic endpoints.
- **Role-based access control** — the current API has no user concept; adding a thin auth layer (Sanctum + policies) 
would allow per-contract ownership and read-only consumer tokens.
- **Pagination and filtering on list endpoints** — assets and contracts can grow large; cursor-based pagination and 
filter-by-status query params would be straightforward additions given the repository interface abstraction.
