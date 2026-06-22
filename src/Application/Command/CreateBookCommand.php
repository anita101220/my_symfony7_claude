<?php

declare(strict_types=1);

namespace App\Application\Command;

final readonly class CreateBookCommand
{
    public function __construct(
        public string $title,
        public string $author,
        public string $isbn,
        public ?string $description,
        public string $price,
    ) {}
}
