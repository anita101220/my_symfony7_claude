<?php

declare(strict_types=1);

namespace App\Application\Handler;

use App\Application\Command\DeleteBookCommand;
use App\Domain\Exception\BookNotFoundException;
use App\Domain\Repository\BookRepositoryInterface;
use Symfony\Component\Uid\Uuid;

final class DeleteBookHandler
{
    public function __construct(
        private readonly BookRepositoryInterface $bookRepository,
    ) {}

    public function handle(DeleteBookCommand $command): void
    {
        $uuid = $this->parseUuid($command->id);
        $book = $this->bookRepository->findById($uuid);

        if ($book === null) {
            throw new BookNotFoundException($command->id);
        }

        $this->bookRepository->delete($book);
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
