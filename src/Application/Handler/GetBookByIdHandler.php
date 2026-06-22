<?php

declare(strict_types=1);

namespace App\Application\Handler;

use App\Application\DTO\Response\BookResponse;
use App\Application\Query\GetBookByIdQuery;
use App\Domain\Exception\BookNotFoundException;
use App\Domain\Repository\BookRepositoryInterface;
use Symfony\Component\Uid\Uuid;

final class GetBookByIdHandler
{
    public function __construct(
        private readonly BookRepositoryInterface $bookRepository,
    ) {}

    public function handle(GetBookByIdQuery $query): BookResponse
    {
        $uuid = $this->parseUuid($query->id);
        $book = $this->bookRepository->findById($uuid);

        if ($book === null) {
            throw new BookNotFoundException($query->id);
        }

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
