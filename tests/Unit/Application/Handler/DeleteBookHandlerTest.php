<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Handler;

use App\Application\Command\DeleteBookCommand;
use App\Application\Handler\DeleteBookHandler;
use App\Domain\Entity\Book;
use App\Domain\Exception\BookNotFoundException;
use App\Domain\Repository\BookRepositoryInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

#[CoversClass(DeleteBookHandler::class)]
final class DeleteBookHandlerTest extends TestCase
{
    private BookRepositoryInterface&MockObject $bookRepository;
    private DeleteBookHandler $handler;

    protected function setUp(): void
    {
        $this->bookRepository = $this->createMock(BookRepositoryInterface::class);
        $this->handler = new DeleteBookHandler($this->bookRepository);
    }

    #[Test]
    public function handleDeletesExistingBook(): void
    {
        // Arrange
        $id = Uuid::v4();
        $book = $this->createMock(Book::class);

        $this->bookRepository->expects($this->once())
            ->method('findById')
            ->willReturn($book);

        $this->bookRepository->expects($this->once())
            ->method('delete')
            ->with($book);

        // Act — no return value to assert, we verify the mock expectations
        $this->handler->handle(new DeleteBookCommand($id->toRfc4122()));
    }

    #[Test]
    public function handleThrowsWhenBookNotFound(): void
    {
        // Arrange
        $this->bookRepository->method('findById')->willReturn(null);
        $this->bookRepository->expects($this->never())->method('delete');

        // Assert
        $this->expectException(BookNotFoundException::class);

        // Act
        $this->handler->handle(new DeleteBookCommand(Uuid::v4()->toRfc4122()));
    }

    #[Test]
    public function handleThrowsWhenIdIsInvalidUuid(): void
    {
        // Arrange
        $this->bookRepository->expects($this->never())->method('findById');
        $this->bookRepository->expects($this->never())->method('delete');

        // Assert
        $this->expectException(BookNotFoundException::class);

        // Act
        $this->handler->handle(new DeleteBookCommand('not-a-uuid'));
    }
}
