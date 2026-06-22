<?php

declare(strict_types=1);

namespace App\Application\DTO\Response;

use App\Domain\Entity\Book;

final readonly class BookResponse
{
    public function __construct(
        public string $id,
        public string $title,
        public string $author,
        public string $isbn,
        public ?string $description,
        public float $price,
        public string $createdAt,
        public string $updatedAt,
    ) {}

    public static function fromEntity(Book $book): self
    {
        return new self(
            id: $book->getId()->toRfc4122(),
            title: $book->getTitle(),
            author: $book->getAuthor(),
            isbn: $book->getIsbn(),
            description: $book->getDescription(),
            price: (float) $book->getPrice(),
            createdAt: $book->getCreatedAt()->format(\DateTimeInterface::ATOM),
            updatedAt: $book->getUpdatedAt()->format(\DateTimeInterface::ATOM),
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'author' => $this->author,
            'isbn' => $this->isbn,
            'description' => $this->description,
            'price' => $this->price,
            'createdAt' => $this->createdAt,
            'updatedAt' => $this->updatedAt,
        ];
    }
}
