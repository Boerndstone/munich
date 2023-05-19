<?php

namespace App\Entity;

use App\Repository\AreaRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity(repositoryClass: AreaRepository::class)]
class Area
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[Assert\NotBlank(message: 'Der Name des Gebiets darf nicht leer sein!')]
    #[Assert\Length(minMessage: 'Der Gebietsname sollte mehr als zwei Zeichen enthalten!', min: 2)]
    #[ORM\Column(type: Types::STRING, length: 255)]
    private ?string $name = null;

    #[Assert\NotNull(message: 'Die URL darf nicht leer sein und darf keine Umlaute enthalten!')]
    #[Assert\Length(minMessage: 'Die URL sollte mehr als zwei Zeichen enthalten!', min: 2)]
    #[ORM\Column(type: Types::STRING, length: 255)]
    private ?string $slug = null;

    #[Assert\NotNull(message: 'Die Angabe zur Lage darf nicht leer sein.')]
    #[ORM\Column(type: Types::STRING, length: 25)]
    private ?string $orientation = null;

    #[ORM\OneToMany(mappedBy: 'area', targetEntity: Rock::class, fetch: 'EXTRA_LAZY')]
    private Collection $rocks;

    #[ORM\OneToMany(mappedBy: 'area', targetEntity: Routes::class, fetch: 'EXTRA_LAZY')]
    private Collection $routes;

    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    #[Assert\Type(type: 'integer', message: 'Bitte nur Zahlenwerte eintragen.')]
    private int $sequence;

    #[ORM\Column(type: Types::SMALLINT)]
    private int $online;

    #[ORM\Column(type: Types::STRING, length: 25, nullable: true)]
    private ?string $image = null;

    #[ORM\Column(type: Types::STRING, length: 25, nullable: true)]
    private ?string $headerImage = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 4, scale: 2, nullable: true)]
    private ?string $lat = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 4, scale: 2, nullable: true)]
    private ?string $lng = null;

    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    private int $zoom;

    public function __construct()
    {
        $this->rocks = new ArrayCollection();
        $this->routes = new ArrayCollection();
        $this->belongsToRock = new ArrayCollection();
    }

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

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): self
    {
        $this->slug = $slug;

        return $this;
    }

    public function getOrientation(): ?string
    {
        return $this->orientation;
    }

    public function setOrientation(string $orientation): self
    {
        $this->orientation = $orientation;

        return $this;
    }

    /**
     * @return Collection|Routes[]
     */
    public function getRoutes(): Collection
    {
        return $this->routes;
    }

    /**
     * @return Collection|Rock[]
     */
    public function getRocks(): Collection
    {
        return $this->rocks;
    }

    public function addRock(Rock $rock): self
    {
        if (!$this->rocks->contains($rock)) {
            $this->rocks[] = $rock;
            $rock->setarea($this);
        }

        return $this;
    }

    public function removeRock(Rock $rock): self
    {
        if ($this->rocks->removeElement($rock)) {
            // set the owning side to null (unless already changed)
            if ($rock->getarea() === $this) {
                $rock->setarea(null);
            }
        }

        return $this;
    }

    public function __toString()
    {
        return $this->getName();
    }

    public function getSequence(): ?int
    {
        return $this->sequence;
    }

    public function setSequence(?int $sequence): self
    {
        $this->sequence = $sequence;

        return $this;
    }

    public function getOnline(): ?int
    {
        return $this->online;
    }

    public function setOnline(int $online): self
    {
        $this->online = $online;

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

    public function setLat(float $lat): self
    {
        $this->lat = $lat;

        return $this;
    }

    public function getLng(): ?float
    {
        return $this->lng;
    }

    public function setLng(float $lng): self
    {
        $this->lng = $lng;

        return $this;
    }

    public function getZoom(): ?int
    {
        return $this->zoom;
    }

    public function setZoom(int $zoom): self
    {
        $this->zoom = $zoom;

        return $this;
    }

    /**
     * @Assert\Callback
     */
    public function validate(ExecutionContextInterface $context, $payload)
    {
        if (stripos($this->getName(), 'the borg') !== false) {
            $context->buildViolation('Um.. the Bork kinda makes us nervous')
                ->atPath('name')
                ->addViolation();
        }
    }
}
