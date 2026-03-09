<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class RockImprovementSuggestion
{
    #[Assert\NotBlank(message: 'Bitte geben Sie Ihren Namen an.')]
    #[Assert\Length(min: 2, max: 255)]
    public ?string $name = null;

    #[Assert\NotBlank]
    public ?string $rockName = null;

    #[Assert\NotBlank(message: 'Bitte geben Sie den Routennamen an.')]
    #[Assert\Length(max: 255)]
    public ?string $routeName = null;

    #[Assert\Length(max: 20)]
    public ?string $grade = null;

    #[Assert\Length(max: 255)]
    public ?string $firstAscent = null;

    public ?string $comment = null;

    /**
     * Honeypot: must stay empty (bots often fill it). Not rendered visibly.
     */
    #[Assert\Blank(message: 'rock_improvement.validation_error')]
    public ?string $website = null;
}
