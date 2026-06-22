# Book API — Symfony 7 REST API with CQRS

A production-quality REST API built with **Symfony 7**, **Doctrine ORM**, and the **CQRS** pattern. Demonstrates CRUD operations on a Books domain following SOLID principles, PSR standards, and PHP 8.3 features.

---

## Architecture

```
src/
├── Application/
│   ├── Command/          # CreateBookCommand, UpdateBookCommand, DeleteBookCommand
│   ├── Query/            # GetBookByIdQuery, GetBooksQuery
│   ├── Handler/          # One handler per command/query
│   └── DTO/
│       ├── Request/      # CreateBookRequest, UpdateBookRequest (with validation)
│       └── Response/     # BookResponse (maps entity → safe DTO)
├── Domain/
│   ├── Entity/           # Book (Doctrine ORM entity, UUID PK)
│   ├── Repository/       # BookRepositoryInterface
│   └── Exception/        # DomainException, BookNotFoundException, BookAlreadyExistsException
├── Infrastructure/
│   └── Repository/       # DoctrineBookRepository (implements interface)
└── Presentation/
    ├── Controller/        # BookController (thin, delegates to handlers)
    └── EventListener/    # ApiExceptionListener (DomainException → JSON response)
```

---

## Requirements

| Tool | Version |
|------|---------|
| PHP  | ≥ 8.3   |
| Composer | ≥ 2.x |
| MySQL | 8.0+ (dev/prod) |
| SQLite | any (tests) |

---

## Setup

### 1. Install dependencies

```bash
composer install
```

### 2. Configure database

Copy and edit `.env` (or create `.env.local` to override):

```bash
cp .env .env.local
# Edit DATABASE_URL in .env.local to match your MySQL credentials
```

### 3. Create database and run migrations

```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
```

### 4. Start the server

```bash
# Using Symfony CLI (recommended)
symfony server:start

# Or PHP built-in server
php -S localhost:8000 -t public/
```

---

## Running Tests

Unit tests use pure PHPUnit mocks — no database required.  
Functional tests use SQLite (configured in `.env.test`).

```bash
# All tests
php vendor/bin/phpunit

# Only unit tests
php vendor/bin/phpunit --testsuite Unit

# Only functional tests
php vendor/bin/phpunit --testsuite Functional
```

---

## API Reference

Base URL: `http://localhost:8000`

All responses follow this envelope:

```json
// Success
{ "success": true, "data": { ... } }

// Error
{ "success": false, "message": "...", "errors": [ ... ] }
```

---

### POST /api/books — Create a book

**Request**
```json
{
  "title": "Clean Code",
  "author": "Robert C. Martin",
  "isbn": "9780132350884",
  "description": "A handbook of agile software craftsmanship",
  "price": 29.99
}
```

**Response — 201 Created**
```json
{
  "success": true,
  "data": {
    "id": "018e1234-5678-7abc-def0-123456789abc",
    "title": "Clean Code",
    "author": "Robert C. Martin",
    "isbn": "9780132350884",
    "description": "A handbook of agile software craftsmanship",
    "price": 29.99,
    "createdAt": "2024-01-01T00:00:00+00:00",
    "updatedAt": "2024-01-01T00:00:00+00:00"
  }
}
```

**Validation error — 422 Unprocessable Entity**
```json
{
  "success": false,
  "message": "Validation failed.",
  "errors": [
    { "field": "title", "message": "Title is required." },
    { "field": "isbn",  "message": "The ISBN \"xyz\" is not valid." }
  ]
}
```

---

### GET /api/books — List all books

**Response — 200 OK**
```json
{
  "success": true,
  "data": [
    {
      "id": "018e1234-...",
      "title": "Clean Code",
      "author": "Robert C. Martin",
      "isbn": "9780132350884",
      "description": null,
      "price": 29.99,
      "createdAt": "2024-01-01T00:00:00+00:00",
      "updatedAt": "2024-01-01T00:00:00+00:00"
    }
  ]
}
```

---

### GET /api/books/{id} — Get a book by ID

**Response — 200 OK** — same shape as the single object above.

**Not found — 404**
```json
{
  "success": false,
  "message": "Book with id \"018e...\" not found.",
  "errors": []
}
```

---

### PUT /api/books/{id} — Update a book

**Request** — same fields as POST (all required).

**Response — 200 OK** — updated book object.

**Conflict — 409** — when the new ISBN belongs to another book.

---

### DELETE /api/books/{id} — Delete a book

**Response — 204 No Content** (empty body).

---

## Example cURL commands

```bash
# Create
curl -s -X POST http://localhost:8000/api/books \
  -H "Content-Type: application/json" \
  -d '{"title":"Clean Code","author":"Robert C. Martin","isbn":"9780132350884","price":29.99}' | jq

# List
curl -s http://localhost:8000/api/books | jq

# Show (replace the UUID)
curl -s http://localhost:8000/api/books/018e1234-5678-7abc-def0-123456789abc | jq

# Update
curl -s -X PUT http://localhost:8000/api/books/018e1234-5678-7abc-def0-123456789abc \
  -H "Content-Type: application/json" \
  -d '{"title":"Clean Code 2e","author":"Robert C. Martin","isbn":"9780132350884","price":34.99}' | jq

# Delete
curl -s -X DELETE http://localhost:8000/api/books/018e1234-5678-7abc-def0-123456789abc
```

---

## Key Design Decisions

| Decision | Reason |
|----------|--------|
| CQRS with direct handler injection | Keeps responsibilities explicit without the overhead of a full message bus for a small domain |
| `readonly` commands/queries/DTOs | Immutability enforced by the PHP runtime — no setter drift |
| `#[\Override]` on interface implementations | PHP 8.3 attribute catches rename/removal mismatches at compile time |
| `BookRepositoryInterface` in Domain | Infrastructure detail (Doctrine) never leaks into Application or Domain layers |
| `ApiExceptionListener` | Controllers stay exception-free; HTTP semantics live in one place |
| SQLite for tests | No MySQL dependency in CI; schema rebuilt from entity metadata on every run |
