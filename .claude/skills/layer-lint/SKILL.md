# Layer Lint Skill

Grep the codebase for architecture violations in this Symfony 7 CQRS project and report findings grouped by severity.

## How to invoke

```
/layer-lint [path]
```

If no path is given, scan the entire `src/` tree. A path argument narrows the scan (e.g., `/layer-lint src/Application`).

## What to check

Run each grep command below using the Grep tool (pattern + path + output_mode: "content"). Collect every hit, then report them grouped by severity at the end. Do NOT stop after the first finding — run all checks.

---

### CRITICAL checks

**C1 — Domain imports Infrastructure**
Grep `src/Domain/` for any reference to `App\Infrastructure`:
```
pattern: App\\Infrastructure
path: src/Domain
```
Fix: Domain must never know about Infrastructure. Move the reference to Application or Infrastructure.

**C2 — Domain imports Doctrine (outside entity ORM attributes)**
Grep `src/Domain/` for `use Doctrine\` in non-entity files (i.e., files not under `src/Domain/Entity/`):
```
pattern: use Doctrine\\
path: src/Domain
glob: !(Entity)/**/*.php
```
If your grep tool cannot negate globs, grep `src/Domain/` broadly and manually exclude hits inside `src/Domain/Entity/` — ORM mapping attributes in entities are an accepted pragmatic exception documented in CLAUDE.md.

**C3 — Application imports Infrastructure concrete class**
Grep `src/Application/` for any reference to `DoctrineBookRepository` or `App\Infrastructure`:
```
pattern: (DoctrineBookRepository|App\\Infrastructure)
path: src/Application
```
Fix: Handlers must depend only on the repository interface (`BookRepositoryInterface`), never the Doctrine implementation.

**C4 — Missing `readonly` on Commands / Queries / request DTOs**
Grep each directory and flag any file that does NOT contain `readonly class`:

```
pattern: ^(final\s+)?class\s+   # matches class declarations lacking readonly
path: src/Application/Command
path: src/Application/Query
path: src/Application/DTO/Request
```

Practical approach: list all `.php` files in those three directories, then grep each for `readonly class`. Any file where `readonly class` is absent is a violation.

**C5 — Handler depends on Doctrine concrete class directly**
Grep `src/Application/Handler/` for `use Doctrine\`:
```
pattern: use Doctrine\\
path: src/Application/Handler
```

---

### WARNING checks

**W1 — Missing `#[\Override]` on interface implementations**
Grep `src/Infrastructure/Repository/` and `src/Domain/Exception/` for public function declarations that implement interface methods but lack `#[\Override]` on the preceding line:
```
pattern: public function (findById|findAll|findByIsbn|save|delete|getHttpStatusCode)
path: src
```
For each hit, check whether the line immediately above starts with `#[\Override]`. Report any that do not.

**W2 — Price typed as `float` instead of `string`**
Grep all of `src/` for float-typed price declarations:
```
pattern: (float\s+\$price|public float \$price)
path: src
```
Fix: `price` must be `string` everywhere — entity, DTOs, response objects. Cast from JSON `float` in the controller only, then immediately call `number_format($dto->price, 2, '.', '')` before building the command.

**W3 — Price cast to float in response DTO**
Grep for `(float) $` or `(float)` combined with price context:
```
pattern: \(float\).*price
path: src
```
Fix: `BookResponse` (and any entity response) must keep price as `string`. Remove the `(float)` cast.

**W4 — UUID generated outside Application handlers**
Grep for `Uuid::v` in layers that must not generate UUIDs:
```
pattern: Uuid::v\d
path: src/Domain
```
```
pattern: Uuid::v\d
path: src/Infrastructure
```
```
pattern: Uuid::v\d
path: src/Presentation
```
Fix: UUID generation belongs only in `src/Application/Handler/Create*Handler.php`.

**W5 — `updatedAt` set manually (not via PreUpdate)**
Grep handlers, repositories, and controllers for manual assignment of `updatedAt`:
```
pattern: updatedAt\s*=
path: src/Application
```
```
pattern: updatedAt\s*=
path: src/Infrastructure
```
```
pattern: updatedAt\s*=
path: src/Presentation
```
Fix: `updatedAt` must only be set inside the `#[ORM\PreUpdate]` callback on the entity.

---

### STYLE checks

**S1 — `try/catch` inside controllers**
```
pattern: try \{
path: src/Presentation/Controller
```
Fix: Controllers must not catch exceptions. All exception-to-response mapping belongs in `ApiExceptionListener`.

**S2 — Repository calls inside controllers**
```
pattern: Repository
path: src/Presentation/Controller
```
Flag any import or usage of a repository inside a controller. Controllers interact only with handlers.

**S3 — Ad-hoc response arrays (not using success/error envelope)**
```
pattern: new JsonResponse\(\[(?!.*'success')
path: src/Presentation
```
All responses must match `{ "success": true/false, "data": ..., "message": ..., "errors": [] }`.

---

## Output format

Group all findings under severity headers. For each finding:

```
[CHECK-ID] src/Path/To/File.php : line N
  Issue: <what is wrong>
  Fix:   <what it should be>
```

Example:
```
### CRITICAL

[C2] src/Domain/Repository/BookRepositoryInterface.php : line 8
  Issue: `use Symfony\Component\Uid\Uuid` is a Symfony import in the Domain layer.
  Fix:   Either introduce a domain-native value object or document this as an accepted
         exception in CLAUDE.md.

### WARNING

[W2] src/Application/DTO/Request/CreateBookRequest.php : line 29
  Issue: `public float $price` — price must be typed string, not float.
  Fix:   Change to `public string $price` and update controller to pass raw string.

### STYLE
(none)
```

If a severity group has no findings, write `(none)` under it.

After all groups, print a one-line summary:
```
Layer lint complete — N critical, N warning, N style findings.
```
