<?php

declare(strict_types=1);

namespace App\Presentation\Controller;

use App\Application\Command\CreateBookCommand;
use App\Application\Command\DeleteBookCommand;
use App\Application\Command\UpdateBookCommand;
use App\Application\DTO\Request\CreateBookRequest;
use Symfony\Component\HttpKernel\Attribute\AsController;
use App\Application\DTO\Request\UpdateBookRequest;
use App\Application\Handler\CreateBookHandler;
use App\Application\Handler\DeleteBookHandler;
use App\Application\Handler\GetBookByIdHandler;
use App\Application\Handler\GetBooksHandler;
use App\Application\Handler\UpdateBookHandler;
use App\Application\Query\GetBookByIdQuery;
use App\Application\Query\GetBooksQuery;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[AsController]
#[Route('/api/books', name: 'api_books_')]
final class BookController
{
    public function __construct(
        private readonly CreateBookHandler $createBookHandler,
        private readonly UpdateBookHandler $updateBookHandler,
        private readonly DeleteBookHandler $deleteBookHandler,
        private readonly GetBookByIdHandler $getBookByIdHandler,
        private readonly GetBooksHandler $getBooksHandler,
        private readonly ValidatorInterface $validator,
    ) {}

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $books = $this->getBooksHandler->handle(new GetBooksQuery());

        return $this->success(array_map(
            static fn($book) => $book->toArray(),
            $books,
        ));
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(string $id): JsonResponse
    {
        $book = $this->getBookByIdHandler->handle(new GetBookByIdQuery($id));

        return $this->success($book->toArray());
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = $this->decodeBody($request);
        if ($data === null) {
            return $this->error('Invalid JSON payload.', [], Response::HTTP_BAD_REQUEST);
        }

        $dto = new CreateBookRequest(
            title: (string) ($data['title'] ?? ''),
            author: (string) ($data['author'] ?? ''),
            isbn: (string) ($data['isbn'] ?? ''),
            description: isset($data['description']) ? (string) $data['description'] : null,
            price: isset($data['price']) ? (float) $data['price'] : 0.0,
        );

        $violations = $this->validator->validate($dto);
        if (count($violations) > 0) {
            return $this->validationError($violations);
        }

        $book = $this->createBookHandler->handle(new CreateBookCommand(
            title: $dto->title,
            author: $dto->author,
            isbn: $dto->isbn,
            description: $dto->description,
            price: number_format($dto->price, 2, '.', ''),
        ));

        return $this->success($book->toArray(), Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    public function update(string $id, Request $request): JsonResponse
    {
        $data = $this->decodeBody($request);
        if ($data === null) {
            return $this->error('Invalid JSON payload.', [], Response::HTTP_BAD_REQUEST);
        }

        $dto = new UpdateBookRequest(
            title: (string) ($data['title'] ?? ''),
            author: (string) ($data['author'] ?? ''),
            isbn: (string) ($data['isbn'] ?? ''),
            description: isset($data['description']) ? (string) $data['description'] : null,
            price: isset($data['price']) ? (float) $data['price'] : 0.0,
        );

        $violations = $this->validator->validate($dto);
        if (count($violations) > 0) {
            return $this->validationError($violations);
        }

        $book = $this->updateBookHandler->handle(new UpdateBookCommand(
            id: $id,
            title: $dto->title,
            author: $dto->author,
            isbn: $dto->isbn,
            description: $dto->description,
            price: number_format($dto->price, 2, '.', ''),
        ));

        return $this->success($book->toArray());
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(string $id): JsonResponse
    {
        $this->deleteBookHandler->handle(new DeleteBookCommand($id));

        return $this->success(null, Response::HTTP_NO_CONTENT);
    }

    /** @return array<string, mixed>|null */
    private function decodeBody(Request $request): ?array
    {
        if ($request->getContent() === '') {
            return [];
        }

        try {
            return json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return null;
        }
    }

    /** @param array<mixed> $data */
    private function success(mixed $data, int $status = Response::HTTP_OK): JsonResponse
    {
        return new JsonResponse(['success' => true, 'data' => $data], $status);
    }

    /** @param array<mixed> $errors */
    private function error(string $message, array $errors = [], int $status = Response::HTTP_INTERNAL_SERVER_ERROR): JsonResponse
    {
        return new JsonResponse([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
        ], $status);
    }

    private function validationError(ConstraintViolationListInterface $violations): JsonResponse
    {
        $errors = [];
        foreach ($violations as $violation) {
            $errors[] = [
                'field' => $violation->getPropertyPath(),
                'message' => $violation->getMessage(),
            ];
        }

        return $this->error('Validation failed.', $errors, Response::HTTP_UNPROCESSABLE_ENTITY);
    }
}
