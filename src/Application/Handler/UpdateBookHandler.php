<?php

declare(strict_types=1);

namespace App\Application\Handler;

use App\Application\Command\UpdateBookCommand;
use App\Application\DTO\Response\BookResponse;
use App\Domain\Exception\BookAlreadyExistsException;
use App\Domain\Exception\BookNotFoundException;
use App\Domain\Repository\BookRepositoryInterface;
use Symfony\Component\Uid\Uuid;

final class UpdateBookHandler
{
    public function __construct(
        private readonly BookRepositoryInterface $bookRepository,
    ) {}

    public function handle(UpdateBookCommand $command): BookResponse
    {
        $uuid = $this->parseUuid($command->id);
        $book = $this->bookRepository->findById($uuid);

        if ($book === null) {
            throw new BookNotFoundException($command->id);
        }

        // Only check for ISBN conflict when the ISBN actually changed.
        if ($book->getIsbn() !== $command->isbn) {
            $conflicting = $this->bookRepository->findByIsbn($command->isbn);
            if ($conflicting !== null) {
                throw new BookAlreadyExistsException($command->isbn);
            }
        }

        $book->update(
            title: $command->title,
            author: $command->author,
            isbn: $command->isbn,
            description: $command->description,
            price: $command->price,
        );

        $this->bookRepository->save($book);

        return BookResponse::fromEntity($book);
    }

    private function parseUuid(string $id): Uuid
    {
        try {
            return Uuid::fromString($id);
        } catch (\InvalidArgumentException) {
            throw new BookNotFoundException($id);
        }
    }
}
