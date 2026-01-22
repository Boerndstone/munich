<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Repository\VideosRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: VideosRepository::class)]
#[ApiResource(
    operations: [
        new Get(normalizationContext: ['groups' => ['video:read']]),
        new GetCollection(normalizationContext: ['groups' => ['video:read']]),
    ]
)]
class Videos
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['video:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[Groups(['video:read'])]
    private ?Area $videoArea = null;

    #[ORM\ManyToOne]
    #[Groups(['video:read'])]
    private ?Rock $videoRocks = null;

    #[ORM\ManyToOne]
    #[Groups(['video:read'])]
    private ?Routes $videoRoutes = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['video:read'])]
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
