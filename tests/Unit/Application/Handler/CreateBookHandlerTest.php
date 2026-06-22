<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Handler;

use App\Application\Command\CreateBookCommand;
use App\Application\DTO\Response\BookResponse;
use App\Application\Handler\CreateBookHandler;
use App\Domain\Entity\Book;
use App\Domain\Exception\BookAlreadyExistsException;
use App\Domain\Repository\BookRepositoryInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

#[CoversClass(CreateBookHandler::class)]
final class CreateBookHandlerTest extends TestCase
{
    private BookRepositoryInterface&MockObject $bookRepository;
    private CreateBookHandler $handler;

    protected function setUp(): void
    {
        $this->bookRepository = $this->createMock(BookRepositoryInterface::class);
        $this->handler = new CreateBookHandler($this->bookRepository);
    }

    #[Test]
    public function handleCreatesBookAndReturnsResponse(): void
    {
        // Arrange
        $command = new CreateBookCommand(
            title: 'Clean Code',
            author: 'Robert C. Martin',
            isbn: '9780132350884',
            description: 'A handbook of agile software craftsmanship',
            price: '29.99',
        );

        $this->bookRepository->expects($this->once())
            ->method('findByIsbn')
            ->with($command->isbn)
            ->willReturn(null);

        $this->bookRepository->expects($this->once())
            ->method('save')
            ->with($this->isInstanceOf(Book::class));

        // Act
        $result = $this->handler->handle($command);

        // Assert
        $this->assertInstanceOf(BookResponse::class, $result);
        $this->assertSame('Clean Code', $result->title);
        $this->assertSame('Robert C. Martin', $result->author);
        $this->assertSame('9780132350884', $result->isbn);
        $this->assertSame('A handbook of agile software craftsmanship', $result->description);
        $this->assertSame(29.99, $result->price);
        $this->assertNotEmpty($result->id);
    }

    #[Test]
    public function handleCreatesBookWithNullDescription(): void
    {
        // Arrange
        $command = new CreateBookCommand(
            title: 'Clean Code',
            author: 'Robert C. Martin',
            isbn: '9780132350884',
            description: null,
            price: '29.99',
        );

        $this->bookRepository->method('findByIsbn')->willReturn(null);
        $this->bookRepository->expects($this->once())->method('save');

        // Act
        $result = $this->handler->handle($command);

        // Assert
        $this->assertNull($result->description);
    }

    #[Test]
    public function handleThrowsWhenIsbnAlreadyExists(): void
    {
        // Arrange
        $command = new CreateBookCommand(
            title: 'Clean Code',
            author: 'Robert C. Martin',
            isbn: '9780132350884',
            description: null,
            price: '29.99',
        );

        $this->bookRepository->expects($this->once())
            ->method('findByIsbn')
            ->willReturn($this->createMock(Book::class));

        $this->bookRepository->expects($this->never())->method('save');

        // Assert
        $this->expectException(BookAlreadyExistsException::class);
        $this->expectExceptionMessage('9780132350884');

        // Act
        $this->handler->handle($command);
    }
}
