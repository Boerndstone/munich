<?php

namespace App\Dto;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints as Assert;

class RockImprovementSuggestion
{
    #[Assert\NotBlank(message: 'rock_improvement.name_required')]
    #[Assert\Length(min: 2, max: 255)]
    public ?string $name = null;

    #[Assert\NotBlank]
    public ?string $rockName = null;

    #[Assert\Length(max: 255)]
    public ?string $routeName = null;

    public ?int $routeId = null;

    #[Assert\Length(max: 20)]
    public ?string $grade = null;

    #[Assert\Length(max: 255)]
    public ?string $firstAscent = null;

    #[Assert\Length(max: 255)]
    public ?string $photographer = null;

    #[Assert\When(
        expression: 'this.email != null',
        constraints: [
            new Assert\Email(message: 'rock_improvement.email_invalid'),
            new Assert\Length(max: 255),
        ],
    )]
    public ?string $email = null;

    public ?string $comment = null;

    #[Assert\File(
        maxSize: '10M',
        mimeTypes: ['image/jpeg', 'image/png', 'image/webp', 'image/gif'],
        maxSizeMessage: 'rock_improvement.image_max_size',
        mimeTypesMessage: 'rock_improvement.image_mime_types',
    )]
    public ?UploadedFile $image = null;

    /**
     * Honeypot: must stay empty (bots often fill it). Not rendered visibly.
     */
    #[Assert\Blank(message: 'rock_improvement.validation_error')]
    public ?string $website = null;
}
