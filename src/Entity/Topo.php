<?php

namespace App\Entity;

use App\Repository\TopoRepository;
use DateTime;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TopoRepository::class)]
class Topo
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Rock::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Rock $rocks = null;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private ?string $name = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $image = null;

    #[ORM\Column(type: Types::BOOLEAN, nullable: true)]
    private bool $withSector = false;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $svg = null;

    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    private int $number;

    #[ORM\Column(nullable: true)]
    private ?array $path = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $pathCollection = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRocks(): ?Rock
    {
        return $this->rocks;
    }

    public function setRocks(?Rock $rocks): self
    {
        $this->rocks = $rocks;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): self
    {
        $this->image = $image;

        return $this;
    }

    public function getWithSector(): ?bool
    {
        return $this->withSector;
    }

    public function setWithSector(?bool $withSector): self
    {
        $this->withSector = $withSector;

        return $this;
    }

    public function getSvg(): ?string
    {
        return $this->svg;
    }

    public function setSvg(?string $svg): self
    {
        $this->svg = $svg;

        return $this;
    }

    public function getNumber(): ?int
    {
        return $this->number;
    }

    public function setNumber(int $number): self
    {
        $this->number = $number;

        return $this;
    }

    public function getPath(): ?array
    {
        return $this->path;
    }

    public function setPath(?array $path): static
    {
        $this->path = $path;

        return $this;
    }

    public function getPathCollection(): ?string
    {
        return $this->pathCollection;
    }

    public function setPathCollection(?string $pathCollection): static
    {
        $this->pathCollection = $pathCollection;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * @ORM\PreUpdate
     */
    public function onPreUpdate()
    {
        $this->updatedAt = new \DateTime();
    }
}
