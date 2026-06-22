<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Entity;

use App\Domain\Entity\Book;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

#[CoversClass(Book::class)]
final class BookTest extends TestCase
{
    private function makeBook(?Uuid $id = null): Book
    {
        return new Book(
            id: $id ?? Uuid::v4(),
            title: 'Clean Code',
            author: 'Robert C. Martin',
            isbn: '9780132350884',
            description: 'A handbook of agile software craftsmanship',
            price: '29.99',
        );
    }

    #[Test]
    public function constructorSetsAllProperties(): void
    {
        // Arrange & Act
        $book = $this->makeBook();

        // Assert
        $this->assertSame('Clean Code', $book->getTitle());
        $this->assertSame('Robert C. Martin', $book->getAuthor());
        $this->assertSame('9780132350884', $book->getIsbn());
        $this->assertSame('A handbook of agile software craftsmanship', $book->getDescription());
        $this->assertSame('29.99', $book->getPrice());
        $this->assertInstanceOf(\DateTimeImmutable::class, $book->getCreatedAt());
        $this->assertInstanceOf(\DateTimeImmutable::class, $book->getUpdatedAt());
    }

    #[Test]
    public function updateChangesAllMutableFields(): void
    {
        // Arrange
        $book = $this->makeBook();

        // Act
        $book->update(
            title: 'The Pragmatic Programmer',
            author: 'David Thomas',
            isbn: '9780135957059',
            description: 'Updated description',
            price: '49.99',
        );

        // Assert
        $this->assertSame('The Pragmatic Programmer', $book->getTitle());
        $this->assertSame('David Thomas', $book->getAuthor());
        $this->assertSame('9780135957059', $book->getIsbn());
        $this->assertSame('Updated description', $book->getDescription());
        $this->assertSame('49.99', $book->getPrice());
    }

    #[Test]
    public function updateAcceptsNullDescription(): void
    {
        // Arrange
        $book = $this->makeBook();

        // Act
        $book->update('Title', 'Author', '9780132350884', null, '9.99');

        // Assert
        $this->assertNull($book->getDescription());
    }

    #[Test]
    public function eachInstanceReceivesAUniqueId(): void
    {
        // Arrange & Act
        $book1 = $this->makeBook();
        $book2 = $this->makeBook();

        // Assert
        $this->assertNotEquals($book1->getId()->toRfc4122(), $book2->getId()->toRfc4122());
    }

    #[Test]
    public function idIsPreservedAsProvided(): void
    {
        // Arrange
        $uuid = Uuid::v4();

        // Act
        $book = $this->makeBook($uuid);

        // Assert
        $this->assertSame($uuid->toRfc4122(), $book->getId()->toRfc4122());
    }
}
