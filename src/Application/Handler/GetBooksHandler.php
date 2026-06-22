<?php

declare(strict_types=1);

namespace App\Application\Handler;

use App\Application\DTO\Response\BookResponse;
use App\Application\Query\GetBooksQuery;
use App\Domain\Entity\Book;
use App\Domain\Repository\BookRepositoryInterface;

final class GetBooksHandler
{
    public function __construct(
        private readonly BookRepositoryInterface $bookRepository,
    ) {}

    /**
     * @return BookResponse[]
     */
    public function handle(GetBooksQuery $query): array
    {
        return array_map(
            static fn(Book $book): BookResponse => BookResponse::fromEntity($book),
            $this->bookRepository->findAll(),
        );
    }
}
