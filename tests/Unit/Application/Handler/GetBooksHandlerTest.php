<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Handler;

use App\Application\DTO\Response\BookResponse;
use App\Application\Handler\GetBooksHandler;
use App\Application\Query\GetBooksQuery;
use App\Domain\Entity\Book;
use App\Domain\Repository\BookRepositoryInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

#[CoversClass(GetBooksHandler::class)]
final class GetBooksHandlerTest extends TestCase
{
    private BookRepositoryInterface&MockObject $bookRepository;
    private GetBooksHandler $handler;

    protected function setUp(): void
    {
        $this->bookRepository = $this->createMock(BookRepositoryInterface::class);
        $this->handler = new GetBooksHandler($this->bookRepository);
    }

    #[Test]
    public function handleReturnsEmptyArrayWhenNoBooksExist(): void
    {
        // Arrange
        $this->bookRepository->method('findAll')->willReturn([]);

        // Act
        $result = $this->handler->handle(new GetBooksQuery());

        // Assert
        $this->assertSame([], $result);
    }

    #[Test]
    public function handleReturnsMappedBookResponses(): void
    {
        // Arrange
        $books = [
            new Book(Uuid::v4(), 'Clean Code', 'Robert C. Martin', '9780132350884', null, '29.99'),
            new Book(Uuid::v4(), 'The Pragmatic Programmer', 'David Thomas', '9780135957059', null, '39.99'),
        ];

        $this->bookRepository->method('findAll')->willReturn($books);

        // Act
        $result = $this->handler->handle(new GetBooksQuery());

        // Assert
        $this->assertCount(2, $result);
        $this->assertContainsOnlyInstancesOf(BookResponse::class, $result);
        $this->assertSame('Clean Code', $result[0]->title);
        $this->assertSame('The Pragmatic Programmer', $result[1]->title);
    }

    #[Test]
    public function handlePreservesOrderFromRepository(): void
    {
        // Arrange
        $first = new Book(Uuid::v4(), 'First Book', 'Author A', '9780132350884', null, '10.00');
        $second = new Book(Uuid::v4(), 'Second Book', 'Author B', '9780135957059', null, '20.00');

        $this->bookRepository->method('findAll')->willReturn([$first, $second]);

        // Act
        $result = $this->handler->handle(new GetBooksQuery());

        // Assert
        $this->assertSame('First Book', $result[0]->title);
        $this->assertSame('Second Book', $result[1]->title);
    }
}
