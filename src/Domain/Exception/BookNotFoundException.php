<?php

declare(strict_types=1);

namespace App\Domain\Exception;

final class BookNotFoundException extends DomainException
{
    public function __construct(string $id)
    {
        parent::__construct(sprintf('Book with id "%s" not found.', $id));
    }

    public function getHttpStatusCode(): int
    {
        return 404;
    }
}
