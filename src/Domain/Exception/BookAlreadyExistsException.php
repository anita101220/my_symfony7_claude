<?php

declare(strict_types=1);

namespace App\Domain\Exception;

final class BookAlreadyExistsException extends DomainException
{
    public function __construct(string $isbn)
    {
        parent::__construct(sprintf('A book with ISBN "%s" already exists.', $isbn));
    }

    public function getHttpStatusCode(): int
    {
        return 409;
    }
}
