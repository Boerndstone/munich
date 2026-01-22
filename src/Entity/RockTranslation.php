<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Repository\RockTranslationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: RockTranslationRepository::class)]
#[ApiResource(
    operations: [
        new Get(normalizationContext: ['groups' => ['rock_translation:read']]),
        new GetCollection(normalizationContext: ['groups' => ['rock_translation:read']]),
    ]
)]
class RockTranslation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['rock_translation:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Rock::class, inversedBy: 'translations')]
    #[Groups(['rock_translation:read'])]
    private ?Rock $rock = null;

    #[ORM\Column(length: 5)]
    #[Groups(['rock_translation:read'])]
    private ?string $locale = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Groups(['rock_translation:read'])]
    private ?string $description = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['rock_translation:read'])]
    private ?string $access = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['rock_translation:read'])]
    private ?string $nature = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['rock_translation:read'])]
    private ?string $flowers = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRock(): ?Rock
    {
        return $this->rock;
    }

    public function setRock(?Rock $rock): static
    {
        $this->rock = $rock;

        return $this;
    }

    public function getLocale(): ?string
    {
        return $this->locale;
    }

    public function setLocale(string $locale): static
    {
        $this->locale = $locale;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getAccess(): ?string
    {
        return $this->access;
    }

    public function setAccess(?string $access): static
    {
        $this->access = $access;

        return $this;
    }

    public function getNature(): ?string
    {
        return $this->nature;
    }

    public function setNature(?string $nature): static
    {
        $this->nature = $nature;

        return $this;
    }

    public function getFlowers(): ?string
    {
        return $this->flowers;
    }

    public function setFlowers(?string $flowers): static
    {
        $this->flowers = $flowers;

        return $this;
    }
}
