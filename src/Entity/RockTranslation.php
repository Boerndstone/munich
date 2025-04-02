<?php

namespace App\Entity;

use App\Repository\RockTranslationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RockTranslationRepository::class)]
class RockTranslation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Rock::class, inversedBy: 'translations')]
    private ?Rock $rock = null;

    #[ORM\Column(length: 5)]
    private ?string $locale = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $description = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $access = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $nature = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
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
