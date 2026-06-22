# Scaffold Skill

Generate all CQRS boilerplate for a new domain entity in this Symfony 7 project.

## How to invoke

```
/scaffold {Entity} [field:type ...]
```

**Examples:**
```
/scaffold Author
/scaffold Author name:string bio:?string birthYear:int
/scaffold Order customerId:string totalAmount:string status:string
```

If no fields are provided, generate a minimal skeleton with a `name: string` field as a placeholder and leave a `// TODO: add fields` comment where additional fields belong.

## Derivations from the entity name

Given `/scaffold Author`:
- `{Entity}` → `Author`
- `{entity}` → `author` (lcfirst)
- `{entities}` → `authors` (lcfirst + s; handle -y → -ies yourself: Category → categories)
- `{EntityTable}` → `authors` (snake_case plural for the ORM table name)

## Files to create

Create all 20 files below. Use the existing `Book` files as the authoritative reference for structure (already in `src/` and `tests/`). The templates here show the required skeleton — fill in field lists from the `/scaffold` arguments.

---

### 1. `src/Domain/Entity/{Entity}.php`

```php
<?php
declare(strict_types=1);
namespace App\Domain\Entity;

use App\Infrastructure\Repository\Doctrine{Entity}Repository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: Doctrine{Entity}Repository::class)]
#[ORM\Table(name: '{EntityTable}')]
#[ORM\HasLifecycleCallbacks]
class {Entity}
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    private Uuid $id;

    // --- fields ---
    // price-like decimals: type: Types::DECIMAL, precision: 10, scale: 2; PHP type: string
    // nullable strings: ?string with nullable: true
    // timestamps below are required on every entity

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $updatedAt;

    public function __construct(Uuid $id, /* fields */) {
        $this->id = $id;
        // assign fields
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    // getters for every field

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function update(/* mutable fields */): void
    {
        // assign updated fields — DO NOT touch $this->updatedAt here; PreUpdate handles it
    }
}
```

**Conventions:**
- `$id` is typed `Uuid`, never `string`.
- Money/decimal fields must be `string` in PHP (column `Types::DECIMAL`).
- `updatedAt` is set ONLY by `#[ORM\PreUpdate]` — never in handlers or `update()`.

---

### 2. `src/Domain/Repository/{Entity}RepositoryInterface.php`

```php
<?php
declare(strict_types=1);
namespace App\Domain\Repository;

use App\Domain\Entity\{Entity};
use Symfony\Component\Uid\Uuid;

interface {Entity}RepositoryInterface
{
    public function findById(Uuid $id): ?{Entity};

    /** @return {Entity}[] */
    public function findAll(): array;

    public function save({Entity} ${entity}): void;

    public function delete({Entity} ${entity}): void;
}
```

Add extra lookup methods (e.g., `findByEmail`) only when the domain actually needs them.

---

### 3. `src/Domain/Exception/{Entity}NotFoundException.php`

```php
<?php
declare(strict_types=1);
namespace App\Domain\Exception;

final class {Entity}NotFoundException extends DomainException
{
    public function __construct(string $id)
    {
        parent::__construct(sprintf('{Entity} with id "%s" not found.', $id));
    }

    #[\Override]
    public function getHttpStatusCode(): int { return 404; }
}
```

---

### 4–6. Commands (`src/Application/Command/`)

All three must be `final readonly class` with constructor property promotion.

```php
// Create{Entity}Command.php
final readonly class Create{Entity}Command
{
    public function __construct(
        public string $name,   // replace with actual fields
        // price-like fields: public string $price
    ) {}
}

// Update{Entity}Command.php
final readonly class Update{Entity}Command
{
    public function __construct(
        public string $id,     // always first
        public string $name,
        // same mutable fields as Create
    ) {}
}

// Delete{Entity}Command.php
final readonly class Delete{Entity}Command
{
    public function __construct(public string $id) {}
}
```

---

### 7–8. Queries (`src/Application/Query/`)

```php
// Get{Entity}ByIdQuery.php
final readonly class Get{Entity}ByIdQuery
{
    public function __construct(public string $id) {}
}

// Get{Entity}sQuery.php
final readonly class Get{Entity}sQuery {}
```

---

### 9–10. Request DTOs (`src/Application/DTO/Request/`)

`final readonly class` with Symfony Validator constraints. **Price fields must be `float` in the DTO** (the controller casts from JSON) but are formatted to `string` before the command is built:

```php
final readonly class Create{Entity}Request
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(max: 255)]
        public string $name,

        // For price/money fields:
        // #[Assert\NotNull]
        // #[Assert\PositiveOrZero]
        // public float $price,
    ) {}
}
```

`Update{Entity}Request` mirrors `Create{Entity}Request` (same fields, no `$id`).

---

### 11. Response DTO (`src/Application/DTO/Response/{Entity}Response.php`)

```php
final readonly class {Entity}Response
{
    public function __construct(
        public string $id,
        public string $name,
        // price: public string $price  (keep as string — do NOT cast to float)
        public string $createdAt,
        public string $updatedAt,
    ) {}

    public static function fromEntity({Entity} ${entity}): self
    {
        return new self(
            id: ${entity}->getId()->toRfc4122(),
            name: ${entity}->getName(),
            createdAt: ${entity}->getCreatedAt()->format(\DateTimeInterface::ATOM),
            updatedAt: ${entity}->getUpdatedAt()->format(\DateTimeInterface::ATOM),
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return ['id' => $this->id, 'name' => $this->name, /* ... */];
    }
}
```

**Convention:** Price in the response DTO stays `string` — never cast to `(float)`.

---

### 12–16. Handlers (`src/Application/Handler/`)

Each handler receives only `{Entity}RepositoryInterface` — never the Doctrine concrete class.

```php
// Create{Entity}Handler.php
final class Create{Entity}Handler
{
    public function __construct(
        private readonly {Entity}RepositoryInterface ${entity}Repository,
    ) {}

    public function handle(Create{Entity}Command $command): {Entity}Response
    {
        $entity = new {Entity}(
            id: Uuid::v4(),
            name: $command->name,
        );
        $this->{entity}Repository->save($entity);
        return {Entity}Response::fromEntity($entity);
    }
}
```

- **UUID is generated here** (`Uuid::v4()`), never in the entity constructor or the repository.
- `Update{Entity}Handler`: call `findById` → throw `{Entity}NotFoundException` if null → call `$entity->update(...)` → `save`.
- `Delete{Entity}Handler`: `findById` → throw if null → `delete`.
- `Get{Entity}ByIdHandler`: `findById` → throw if null → return response.
- `Get{Entity}sHandler`: `findAll()` → map to `array<{Entity}Response>`.

All handlers share the same `parseUuid` private helper (copy from `UpdateBookHandler`).

---

### 17. `src/Infrastructure/Repository/Doctrine{Entity}Repository.php`

```php
/** @extends ServiceEntityRepository<{Entity}> */
final class Doctrine{Entity}Repository extends ServiceEntityRepository implements {Entity}RepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, {Entity}::class);
    }

    #[\Override]
    public function findById(Uuid $id): ?{Entity} { return $this->find($id); }

    #[\Override]
    /** @return {Entity}[] */
    public function findAll(): array { return $this->findBy([], ['createdAt' => 'DESC']); }

    #[\Override]
    public function save({Entity} ${entity}): void
    {
        $this->getEntityManager()->persist(${entity});
        $this->getEntityManager()->flush();
    }

    #[\Override]
    public function delete({Entity} ${entity}): void
    {
        $this->getEntityManager()->remove(${entity});
        $this->getEntityManager()->flush();
    }
}
```

**Every method that implements an interface method must have `#[\Override]`.**

---

### 18. `src/Presentation/Controller/{Entity}Controller.php`

```php
#[AsController]
#[Route('/api/{entities}', name: 'api_{entities}_')]
final class {Entity}Controller
{
    public function __construct(
        private readonly Create{Entity}Handler $create{Entity}Handler,
        private readonly Update{Entity}Handler $update{Entity}Handler,
        private readonly Delete{Entity}Handler $delete{Entity}Handler,
        private readonly Get{Entity}ByIdHandler $get{Entity}ByIdHandler,
        private readonly Get{Entity}sHandler $get{Entity}sHandler,
        private readonly ValidatorInterface $validator,
    ) {}

    // list, show, create, update, delete actions
    // copy the decodeBody / success / error / validationError helpers from BookController
}
```

**Controller rules:**
- No `try/catch` — exceptions propagate to `ApiExceptionListener`.
- No repository calls — only handler calls.
- Price from JSON: cast to `float`, validate, then `number_format($dto->price, 2, '.', '')` before the command.
- Response envelope: always `{ "success": true/false, "data": ..., "message": ..., "errors": [] }`.

---

### 19. `tests/Unit/Application/Handler/Create{Entity}HandlerTest.php`

```php
#[CoversClass(Create{Entity}Handler::class)]
final class Create{Entity}HandlerTest extends TestCase
{
    private {Entity}RepositoryInterface&MockObject ${entity}Repository;
    private Create{Entity}Handler $handler;

    protected function setUp(): void
    {
        $this->{entity}Repository = $this->createMock({Entity}RepositoryInterface::class);
        $this->handler = new Create{Entity}Handler($this->{entity}Repository);
    }

    #[Test]
    public function handle{Entity}AndReturnsResponse(): void { /* ... */ }

    #[Test]
    public function handleThrowsWhen{Entity}NotFound(): void { /* ... */ }
}
```

Unit tests: no database, no Symfony container — only `MockObject` of the repository interface.

---

### 20. `tests/Functional/Controller/{Entity}ControllerTest.php`

```php
final class {Entity}ControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $this->entityManager = $em;

        $schemaTool = new SchemaTool($this->entityManager);
        $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool->dropSchema($metadata);
        $schemaTool->createSchema($metadata);
    }

    // test{Entity}Create201, test{Entity}Returns422, test{Entity}Returns404, etc.
}
```

---

## After creating all files

1. Run `php bin/console cache:clear` to pick up new services.
2. Run `php bin/console doctrine:schema:update --force` (dev only) or create a migration.
3. Run `php vendor/bin/phpunit` to verify tests pass.
4. Announce each created file path so the user can review them.
