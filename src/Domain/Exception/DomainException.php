<?php

declare(strict_types=1);

namespace App\Domain\Exception;

/**
 * Base class for all domain exceptions.
 * Carries the HTTP status code so the presentation layer stays thin.
 */
abstract class DomainException extends \RuntimeException
{
    abstract public function getHttpStatusCode(): int;
}
