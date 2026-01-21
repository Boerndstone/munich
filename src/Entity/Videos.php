<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Repository\VideosRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: VideosRepository::class)]
#[ApiResource(
    operations: [
        new Get(),
        new GetCollection(),
    ]
)]
class Videos
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    private ?Area $videoArea = null;

    #[ORM\ManyToOne]
    private ?Rock $videoRocks = null;

    #[ORM\ManyToOne]
    private ?Routes $videoRoutes = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $videoLink = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getVideoArea(): ?Area
    {
        return $this->videoArea;
    }

    public function setVideoArea(?Area $videoArea): self
    {
        $this->videoArea = $videoArea;

        return $this;
    }

    public function getVideoRocks(): ?Rock
    {
        return $this->videoRocks;
    }

    public function setVideoRocks(?Rock $videoRocks): self
    {
        $this->videoRocks = $videoRocks;

        return $this;
    }

    public function getVideoRoutes(): ?Routes
    {
        return $this->videoRoutes;
    }

    public function setVideoRoutes(?Routes $videoRoutes): self
    {
        $this->videoRoutes = $videoRoutes;

        return $this;
    }

    public function getVideoLink(): ?string
    {
        return $this->videoLink;
    }

    public function setVideoLink(string $videoLink): self
    {
        $this->videoLink = $videoLink;

        return $this;
    }
}
