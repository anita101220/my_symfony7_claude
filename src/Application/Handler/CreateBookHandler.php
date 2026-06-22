<?php

declare(strict_types=1);

namespace App\Application\Handler;

use App\Application\Command\CreateBookCommand;
use App\Application\DTO\Response\BookResponse;
use App\Domain\Entity\Book;
use App\Domain\Exception\BookAlreadyExistsException;
use App\Domain\Repository\BookRepositoryInterface;
use Symfony\Component\Uid\Uuid;

final class CreateBookHandler
{
    public function __construct(
        private readonly BookRepositoryInterface $bookRepository,
    ) {}

    public function handle(CreateBookCommand $command): BookResponse
    {
        if ($this->bookRepository->findByIsbn($command->isbn) !== null) {
            throw new BookAlreadyExistsException($command->isbn);
        }

        $book = new Book(
            id: Uuid::v4(),
            title: $command->title,
            author: $command->author,
            isbn: $command->isbn,
            description: $command->description,
            price: $command->price,
        );

        $this->bookRepository->save($book);

        return BookResponse::fromEntity($book);
    }
}
