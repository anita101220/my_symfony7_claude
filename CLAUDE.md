# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Commands

```bash
# Install dependencies
composer install

# Database setup
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate

# Start server
symfony server:start
# or
php -S localhost:8000 -t public/

# Run all tests
php vendor/bin/phpunit

# Run a single test suite
php vendor/bin/phpunit --testsuite Unit
php vendor/bin/phpunit --testsuite Integration
php vendor/bin/phpunit --testsuite Functional

# Run a single test file
php vendor/bin/phpunit tests/Unit/Application/Handler/CreateBookHandlerTest.php
```

## Architecture

This is a **Symfony 7.2 REST API** following strict **CQRS** and **Clean Architecture** layering. The four layers are:

- **Domain** (`src/Domain/`) — pure PHP: `Book` entity, `BookRepositoryInterface`, and domain exceptions. No Symfony or Doctrine dependencies may leak in here.
- **Application** (`src/Application/`) — use cases only. Commands/Queries are `readonly` value objects. Each Handler is injected with the repository interface, never the concrete Doctrine class.
- **Infrastructure** (`src/Infrastructure/`) — `DoctrineBookRepository` implements `BookRepositoryInterface`. This is the only place Doctrine is used.
- **Presentation** (`src/Presentation/`) — `BookController` (thin: decode → validate → command → respond) and `ApiExceptionListener` (converts `DomainException` subclasses to JSON; controllers never catch exceptions).

### Request flow

```
HTTP Request
  → BookController (decode JSON, validate DTO with Symfony Validator)
  → Command/Query object (readonly)
  → Handler (calls repository interface)
  → BookResponse DTO (maps entity fields → safe array)
  → JsonResponse { success, data }

On DomainException anywhere in the chain:
  → ApiExceptionListener → JsonResponse { success: false, message, errors: [] }
```

### Key conventions

- All commands, queries, and request/response DTOs are `readonly`.
- `#[\Override]` is used on all interface implementations — PHP 8.3 compile-time safety.
- `price` is stored as `DECIMAL(10,2)` and typed as `string` in PHP to avoid float precision loss. It is formatted with `number_format(..., 2, '.', '')` before being passed to a command.
- `Book::$id` is a Symfony `Uuid` (not a raw string). Handlers generate the UUID; the entity never auto-generates it.
- `updatedAt` is set via `#[ORM\PreUpdate]` lifecycle callback, not manually in handlers.
- `isbn` is stored as-provided (with or without dashes), column length 17.

### Test strategy

- **Unit** (`tests/Unit/`) — handlers tested with PHPUnit mocks of `BookRepositoryInterface`. No database, no Symfony container.
- **Functional** (`tests/Functional/`) — full HTTP-level tests using Symfony's `WebTestCase` + SQLite. Schema is rebuilt from entity metadata on each run (configured in `.env.test`).
- There is no Integration test suite yet despite the `phpunit.xml.dist` entry.
