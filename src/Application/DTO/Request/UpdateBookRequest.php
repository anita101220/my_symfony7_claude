<?php

declare(strict_types=1);

namespace App\Application\DTO\Request;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class UpdateBookRequest
{
    public function __construct(
        #[Assert\NotBlank(message: 'Title is required.')]
        #[Assert\Length(max: 255, maxMessage: 'Title cannot exceed 255 characters.')]
        public string $title,

        #[Assert\NotBlank(message: 'Author is required.')]
        #[Assert\Length(max: 255, maxMessage: 'Author cannot exceed 255 characters.')]
        public string $author,

        #[Assert\NotBlank(message: 'ISBN is required.')]
        #[Assert\Isbn(message: 'The ISBN "{{ value }}" is not valid.')]
        public string $isbn,

        #[Assert\Length(max: 5000, maxMessage: 'Description cannot exceed 5000 characters.')]
        public ?string $description,

        #[Assert\NotNull(message: 'Price is required.')]
        #[Assert\PositiveOrZero(message: 'Price must be zero or a positive number.')]
        public float $price,
    ) {}
}
