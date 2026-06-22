<?php

declare(strict_types=1);

namespace App\Presentation\EventListener;

use App\Domain\Exception\DomainException;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Converts domain exceptions into standardised JSON error responses.
 * Unhandled exceptions bubble up to Symfony's default error handler.
 */
#[AsEventListener(event: KernelEvents::EXCEPTION, priority: 10)]
final class ApiExceptionListener
{
    public function __invoke(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        if (!$exception instanceof DomainException) {
            return;
        }

        $event->setResponse(new JsonResponse(
            [
                'success' => false,
                'message' => $exception->getMessage(),
                'errors' => [],
            ],
            $exception->getHttpStatusCode(),
        ));
    }
}
