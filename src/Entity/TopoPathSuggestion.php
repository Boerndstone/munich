<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\TopoPathSuggestionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TopoPathSuggestionRepository::class)]
class TopoPathSuggestion
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Rock $rock = null;

    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    private ?int $topoNumber = null;

    #[ORM\Column(type: Types::TEXT)]
    private string $pathCollection = '';

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $referenceImageBasename = null;

    #[ORM\Column(length: 255)]
    private string $uploaderName = '';

    #[ORM\Column(length: 255)]
    private string $uploaderEmail = '';

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $comment = null;

    #[ORM\Column(length: 20, options: ['default' => 'pending'])]
    private string $status = 'pending';

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRock(): ?Rock
    {
        return $this->rock;
    }

    public function setRock(?Rock $rock): self
    {
        $this->rock = $rock;

        return $this;
    }

    public function getTopoNumber(): ?int
    {
        return $this->topoNumber;
    }

    public function setTopoNumber(?int $topoNumber): self
    {
        $this->topoNumber = $topoNumber;

        return $this;
    }

    public function getPathCollection(): string
    {
        return $this->pathCollection;
    }

    public function setPathCollection(string $pathCollection): self
    {
        $this->pathCollection = $pathCollection;

        return $this;
    }

    public function getReferenceImageBasename(): ?string
    {
        return $this->referenceImageBasename;
    }

    public function setReferenceImageBasename(?string $referenceImageBasename): self
    {
        $this->referenceImageBasename = $referenceImageBasename;

        return $this;
    }

    public function getUploaderName(): string
    {
        return $this->uploaderName;
    }

    public function setUploaderName(string $uploaderName): self
    {
        $this->uploaderName = $uploaderName;

        return $this;
    }

    public function getUploaderEmail(): string
    {
        return $this->uploaderEmail;
    }

    public function setUploaderEmail(string $uploaderEmail): self
    {
        $this->uploaderEmail = $uploaderEmail;

        return $this;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(?string $comment): self
    {
        $this->comment = $comment;

        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }
}
