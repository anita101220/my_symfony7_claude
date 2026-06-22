<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\Book;
use Symfony\Component\Uid\Uuid;

interface BookRepositoryInterface
{
    public function findById(Uuid $id): ?Book;

    /** @return Book[] */
    public function findAll(): array;

    public function findByIsbn(string $isbn): ?Book;

    public function save(Book $book): void;

    public function delete(Book $book): void;
}
