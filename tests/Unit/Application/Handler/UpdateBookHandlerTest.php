<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Handler;

use App\Application\Command\UpdateBookCommand;
use App\Application\DTO\Response\BookResponse;
use App\Application\Handler\UpdateBookHandler;
use App\Domain\Entity\Book;
use App\Domain\Exception\BookAlreadyExistsException;
use App\Domain\Exception\BookNotFoundException;
use App\Domain\Repository\BookRepositoryInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

#[CoversClass(UpdateBookHandler::class)]
final class UpdateBookHandlerTest extends TestCase
{
    private BookRepositoryInterface&MockObject $bookRepository;
    private UpdateBookHandler $handler;

    protected function setUp(): void
    {
        $this->bookRepository = $this->createMock(BookRepositoryInterface::class);
        $this->handler = new UpdateBookHandler($this->bookRepository);
    }

    private function makeBook(Uuid $id, string $isbn = '9780132350884'): Book
    {
        return new Book(
            id: $id,
            title: 'Original Title',
            author: 'Original Author',
            isbn: $isbn,
            description: 'Original description',
            price: '29.99',
        );
    }

    #[Test]
    public function handleUpdatesBookSuccessfully(): void
    {
        // Arrange
        $id = Uuid::v4();
        $book = $this->makeBook($id);

        $command = new UpdateBookCommand(
            id: $id->toRfc4122(),
            title: 'Updated Title',
            author: 'Updated Author',
            isbn: '9780132350884', // same ISBN — no conflict check
            description: 'Updated description',
            price: '39.99',
        );

        $this->bookRepository->expects($this->once())
            ->method('findById')
            ->willReturn($book);

        $this->bookRepository->expects($this->never())->method('findByIsbn');
        $this->bookRepository->expects($this->once())->method('save');

        // Act
        $result = $this->handler->handle($command);

        // Assert
        $this->assertInstanceOf(BookResponse::class, $result);
        $this->assertSame('Updated Title', $result->title);
        $this->assertSame('Updated Author', $result->author);
    }

    #[Test]
    public function handleChecksIsbnConflictWhenIsbnChanges(): void
    {
        // Arrange
        $id = Uuid::v4();
        $book = $this->makeBook($id, '9780132350884');
        $newIsbn = '9780201633610';

        $command = new UpdateBookCommand(
            id: $id->toRfc4122(),
            title: 'Title',
            author: 'Author',
            isbn: $newIsbn,
            description: null,
            price: '29.99',
        );

        $this->bookRepository->method('findById')->willReturn($book);

        $this->bookRepository->expects($this->once())
            ->method('findByIsbn')
            ->with($newIsbn)
            ->willReturn(null); // no conflict

        $this->bookRepository->expects($this->once())->method('save');

        // Act
        $result = $this->handler->handle($command);

        // Assert
        $this->assertSame($newIsbn, $result->isbn);
    }

    #[Test]
    public function handleThrowsWhenNewIsbnConflicts(): void
    {
        // Arrange
        $id = Uuid::v4();
        $book = $this->makeBook($id, '9780132350884');
        $newIsbn = '9780201633610';

        $command = new UpdateBookCommand(
            id: $id->toRfc4122(),
            title: 'Title',
            author: 'Author',
            isbn: $newIsbn,
            description: null,
            price: '29.99',
        );

        $this->bookRepository->method('findById')->willReturn($book);
        $this->bookRepository->method('findByIsbn')->willReturn($this->createMock(Book::class));

        // Assert
        $this->expectException(BookAlreadyExistsException::class);

        // Act
        $this->handler->handle($command);
    }

    #[Test]
    public function handleThrowsWhenBookNotFound(): void
    {
        // Arrange
        $command = new UpdateBookCommand(
            id: Uuid::v4()->toRfc4122(),
            title: 'Title',
            author: 'Author',
            isbn: '9780132350884',
            description: null,
            price: '29.99',
        );

        $this->bookRepository->method('findById')->willReturn(null);

        // Assert
        $this->expectException(BookNotFoundException::class);

        // Act
        $this->handler->handle($command);
    }

    #[Test]
    public function handleThrowsWhenIdIsInvalidUuid(): void
    {
        // Arrange
        $command = new UpdateBookCommand(
            id: 'not-a-valid-uuid',
            title: 'Title',
            author: 'Author',
            isbn: '9780132350884',
            description: null,
            price: '29.99',
        );

        // Assert
        $this->expectException(BookNotFoundException::class);

        // Act
        $this->handler->handle($command);
    }
}
