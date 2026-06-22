<?php

declare(strict_types=1);

namespace App\Application\Command;

final readonly class DeleteBookCommand
{
    public function __construct(
        public string $id,
    ) {}
}
