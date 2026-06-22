# Code Review Skill

Review PHP files in this Symfony 7 CQRS project against the architecture rules and conventions defined in CLAUDE.md.

## How to invoke

```
/code-review [file or directory]
```

If no argument is given, review all staged or recently changed `.php` files.

## Review checklist

### Layer separation

- **Domain** (`src/Domain/`): must not import anything from `Symfony\`, `Doctrine\`, or `App\Infrastructure\`. Pure PHP only.
- **Application** (`src/Application/`): handlers must depend only on `BookRepositoryInterface` (domain), never on `DoctrineBookRepository` (infrastructure).
- **Infrastructure** (`src/Infrastructure/`): the only layer allowed to use Doctrine concrete classes.
- **Presentation** (`src/Presentation/`): controllers must not contain business logic. All exception handling belongs in `ApiExceptionListener`, not in try/catch blocks inside controllers.

### Commands, Queries, and DTOs

- All classes under `src/Application/Command/`, `src/Application/Query/`, and `src/Application/DTO/` must be declared `readonly`.
- Constructor property promotion is preferred for these value objects.

### Interface implementations

- Every class that implements an interface must annotate each overriding method with `#[\Override]`.

### Price field

- `price` must be typed as `string` in the entity and DTOs — never `float` or `int`.
- Before passing to a command, price must be formatted with `number_format($price, 2, '.', '')`.

### UUID / ID generation

- UUIDs are generated in handlers (`Uuid::v7()` or similar), never inside the entity constructor or the repository.
- The entity's `$id` field is typed `Uuid`, not `string`.

### Timestamps

- `updatedAt` must be set via the `#[ORM\PreUpdate]` lifecycle callback on the entity — not set manually in handlers or repositories.

### Controller shape

- `BookController` methods must follow: decode JSON → validate DTO → build command/query → call handler → return `JsonResponse`.
- No `try/catch`, no repository calls, no direct entity manipulation inside the controller.

### Response envelope

All JSON responses must use one of these shapes — no ad-hoc arrays:

```json
// Success
{ "success": true, "data": { ... } }

// Error
{ "success": false, "message": "...", "errors": [ ... ] }
```

### Tests

- Files under `tests/Unit/` must mock `BookRepositoryInterface` — no database, no Symfony container.
- Files under `tests/Functional/` must extend `WebTestCase` and use the SQLite test database (APP_ENV=test).
- Do not add Doctrine fixtures or real DB calls to unit tests.

## Output format

For each finding, report:

```
[LAYER | CONVENTION] path/to/File.php : line N
  Issue: <what is wrong>
  Fix:   <what it should be>
```

Group findings by severity: **Critical** (layer violation, missing readonly, direct Doctrine in Application) → **Warning** (missing #[\Override], float price) → **Style** (response envelope deviation, controller fat).

If no issues are found, say so explicitly.
