<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Repository\PhotosRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: PhotosRepository::class)]
#[ApiResource(
    operations: [
        new Get(),
        new GetCollection(),
    ]
)]
class Photos
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    private ?Area $belongsToArea = null;

    #[ORM\ManyToOne]
    private ?Rock $belongsToRock = null;

    #[ORM\ManyToOne]
    private ?Routes $belongsToRoute = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Bitte Namen wählen')]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $photgrapher = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getBelongsToArea(): ?Area
    {
        return $this->belongsToArea;
    }

    public function setBelongsToArea(?Area $belongsToArea): self
    {
        $this->belongsToArea = $belongsToArea;

        return $this;
    }

    public function getBelongsToRock(): ?Rock
    {
        return $this->belongsToRock;
    }

    public function setBelongsToRock(?Rock $belongsToRock): self
    {
        $this->belongsToRock = $belongsToRock;

        return $this;
    }

    public function getBelongsToRoute(): ?Routes
    {
        return $this->belongsToRoute;
    }

    public function setBelongsToRoute(?Routes $belongsToRoute): self
    {
        $this->belongsToRoute = $belongsToRoute;

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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getPhotgrapher(): ?string
    {
        return $this->photgrapher;
    }

    public function setPhotgrapher(string $photgrapher): self
    {
        $this->photgrapher = $photgrapher;

        return $this;
    }
}
