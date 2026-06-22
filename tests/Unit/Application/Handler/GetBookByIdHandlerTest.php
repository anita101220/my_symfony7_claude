<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Handler;

use App\Application\DTO\Response\BookResponse;
use App\Application\Handler\GetBookByIdHandler;
use App\Application\Query\GetBookByIdQuery;
use App\Domain\Entity\Book;
use App\Domain\Exception\BookNotFoundException;
use App\Domain\Repository\BookRepositoryInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

#[CoversClass(GetBookByIdHandler::class)]
final class GetBookByIdHandlerTest extends TestCase
{
    private BookRepositoryInterface&MockObject $bookRepository;
    private GetBookByIdHandler $handler;

    protected function setUp(): void
    {
        $this->bookRepository = $this->createMock(BookRepositoryInterface::class);
        $this->handler = new GetBookByIdHandler($this->bookRepository);
    }

    #[Test]
    public function handleReturnsBookResponseForExistingBook(): void
    {
        // Arrange
        $id = Uuid::v4();
        $book = new Book(
            id: $id,
            title: 'Clean Code',
            author: 'Robert C. Martin',
            isbn: '9780132350884',
            description: 'A handbook of agile software craftsmanship',
            price: '29.99',
        );

        $this->bookRepository->expects($this->once())
            ->method('findById')
            ->with($this->callback(fn(Uuid $uuid): bool => $uuid->toRfc4122() === $id->toRfc4122()))
            ->willReturn($book);

        // Act
        $result = $this->handler->handle(new GetBookByIdQuery($id->toRfc4122()));

        // Assert
        $this->assertInstanceOf(BookResponse::class, $result);
        $this->assertSame($id->toRfc4122(), $result->id);
        $this->assertSame('Clean Code', $result->title);
        $this->assertSame(29.99, $result->price);
    }

    #[Test]
    public function handleThrowsWhenBookNotFound(): void
    {
        // Arrange
        $this->bookRepository->method('findById')->willReturn(null);

        // Assert
        $this->expectException(BookNotFoundException::class);

        // Act
        $this->handler->handle(new GetBookByIdQuery(Uuid::v4()->toRfc4122()));
    }

    #[Test]
    public function handleThrowsWhenIdIsNotAValidUuid(): void
    {
        // Arrange — repository should never be called for an invalid UUID
        $this->bookRepository->expects($this->never())->method('findById');

        // Assert
        $this->expectException(BookNotFoundException::class);

        // Act
        $this->handler->handle(new GetBookByIdQuery('this-is-not-a-uuid'));
    }
}
