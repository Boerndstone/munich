<?php

namespace App\Entity;

use App\Repository\RockRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: RockRepository::class)]
class Rock
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[Assert\NotBlank(message: 'Felsname darf nicht leer sein!')]
    #[Assert\Length(minMessage: 'Felsname sollte mehr als zwei Zeichen enthalten!', min: 2)]
    #[ORM\Column(type: Types::STRING, length: 255)]
    private ?string $name = null;

    #[ORM\OneToMany(mappedBy: 'rock', targetEntity: Routes::class, fetch: 'EXTRA_LAZY')]
    private Collection $routes;

    #[ORM\ManyToOne(targetEntity: Area::class, inversedBy: 'rocks')]
    private ?Area $area = null;

    #[Assert\NotNull(message: 'URL darf nicht leer sein und darf keine Umlaute enthalten!')]
    #[Assert\Length(minMessage: 'URL sollte mehr als zwei Zeichen enthalten!', min: 2)]
    #[ORM\Column(type: Types::STRING, length: 255)]
    private ?string $slug = null;

    #[ORM\Column(type: Types::INTEGER)]
    protected ?int $nr = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $access = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $nature = null;

    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    private ?int $zone = null;

    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    private ?int $banned = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    protected ?int $height = null;

    #[ORM\Column(type: Types::STRING, length: 50, nullable: true)]
    private ?string $orientation = null;

    #[ORM\Column(type: Types::STRING, length: 50, nullable: true)]
    private ?string $season = null;

    #[ORM\Column(nullable: true)]
    private ?bool $childFriendly = null;

    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    private int $sunny;

    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    private int $rain;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $image = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $headerImage = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 6, nullable: true)]
    private ?string $lat = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 6, nullable: true)]
    private ?string $lng = null;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $online = false;

    #[ORM\Column(nullable: true)]
    private ?array $pathCoordinates = null;

    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    private ?int $zoom = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $previewImage = null;

    #[ORM\Column(nullable: true)]
    private ?bool $train = null;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getArea(): ?Area
    {
        return $this->area;
    }

    public function setArea(?Area $area): self
    {
        $this->area = $area;

        return $this;
    }

    public function __toString()
    {
        return $this->getName();
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): self
    {
        $this->slug = $slug;

        return $this;
    }

    public function getNr(): ?int
    {
        return $this->nr;
    }

    public function setNr(?int $nr): self
    {
        $this->nr = $nr;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getAccess(): ?string
    {
        return $this->access;
    }

    public function setAccess(?string $access): self
    {
        $this->access = $access;

        return $this;
    }

    public function getNature(): ?string
    {
        return $this->nature;
    }

    public function setNature(?string $nature): self
    {
        $this->nature = $nature;

        return $this;
    }

    public function getZone(): ?int
    {
        return $this->zone;
    }

    public function setZone(?int $zone): self
    {
        $this->zone = $zone;

        return $this;
    }

    public function getBanned(): ?int
    {
        return $this->banned;
    }

    public function setBanned(?int $banned): self
    {
        $this->banned = $banned;

        return $this;
    }

    public function getHeight(): ?int
    {
        return $this->height;
    }

    public function setHeight(?int $height): self
    {
        $this->height = $height;

        return $this;
    }

    public function getOrientation(): ?string
    {
        return $this->orientation;
    }

    public function setOrientation(?string $orientation): self
    {
        $this->orientation = $orientation;

        return $this;
    }

    public function getSeason(): ?string
    {
        return $this->season;
    }

    public function setSeason(?string $season): self
    {
        $this->season = $season;

        return $this;
    }

    public function getchildFriendly(): ?bool
    {
        return $this->childFriendly;
    }

    public function setchildFriendly(?bool $childFriendly): self
    {
        $this->childFriendly = $childFriendly;

        return $this;
    }

    public function getSunny(): ?int
    {
        return $this->sunny;
    }

    public function setSunny(?int $sunny): self
    {
        $this->sunny = $sunny;

        return $this;
    }

    public function getRain(): ?int
    {
        return $this->rain;
    }

    public function setRain(?int $rain): self
    {
        $this->rain = $rain;

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

    public function getHeaderImage(): ?string
    {
        return $this->headerImage;
    }

    public function setHeaderImage(?string $headerImage): self
    {
        $this->headerImage = $headerImage;

        return $this;
    }

    public function getLat(): ?float
    {
        return $this->lat;
    }

    public function setLat(?float $lat): self
    {
        $this->lat = $lat;

        return $this;
    }

    public function getLng(): ?float
    {
        return $this->lng;
    }

    public function setLng(?float $lng): self
    {
        $this->lng = $lng;

        return $this;
    }

    public function getOnline(): ?bool
    {
        return $this->online;
    }

    public function setOnline(?bool $online): self
    {
        $this->online = $online;

        return $this;
    }

    public function __construct()
    {
        $this->routes = new ArrayCollection();
    }

    /**
     * @return Collection|Routes[]
     */
    public function getRoutes(): Collection
    {
        return $this->routes;
    }

    /**
     * @return ArrayCollection|Routes[]
     */
    public function getRoutesDavid(): ArrayCollection|array
    {
        return $this->routes->toArray();
    }

    public function getPathCoordinates(): ?array
    {
        return $this->pathCoordinates;
    }

    public function setPathCoordinates(?array $pathCoordinates): static
    {
        $this->pathCoordinates = $pathCoordinates;

        return $this;
    }

    public function getZoom(): ?int
    {
        return $this->zoom;
    }

    public function setZoom(?int $zoom): static
    {
        $this->zoom = $zoom;

        return $this;
    }

    public function getPreviewImage(): ?string
    {
        return $this->previewImage;
    }

    public function setPreviewImage(?string $previewImage): static
    {
        $this->previewImage = $previewImage;

        return $this;
    }

    public function isTrain(): ?bool
    {
        return $this->train;
    }

    public function setTrain(?bool $train): static
    {
        $this->train = $train;

        return $this;
    }
}
