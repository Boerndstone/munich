<?php

namespace App\Entity;

use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Repository\TopoRepository;
use DateTime;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: TopoRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ApiResource(
    operations: [
        new Get(normalizationContext: ['groups' => ['topo:read']]),
        new GetCollection(normalizationContext: ['groups' => ['topo:read']]),
    ]
)]
#[ApiFilter(SearchFilter::class, properties: ['rocks.id' => 'exact'])]
#[ApiFilter(OrderFilter::class, properties: ['updatedAt' => 'DESC'])]
class Topo
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['topo:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Rock::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['topo:read'])]
    private ?Rock $rocks = null;

    #[ORM\Column(type: Types::STRING, length: 255)]
    #[Groups(['topo:read'])]
    private ?string $name = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    #[Groups(['topo:read'])]
    private ?string $image = null;

    #[ORM\Column(type: Types::BOOLEAN, nullable: true)]
    #[Groups(['topo:read'])]
    private bool $withSector = false;

    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    #[Groups(['topo:read'])]
    private int $number;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['topo:read'])]
    private ?string $pathCollection = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Groups(['topo:read'])]
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

    public function getNumber(): ?int
    {
        return $this->number;
    }

    public function setNumber(int $number): self
    {
        $this->number = $number;

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

    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function touchUpdatedAt(): void
    {
        $this->updatedAt = new \DateTime();
    }
}
