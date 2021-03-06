<?php

namespace App\Entity;

use App\Repository\AreaRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * @ORM\Entity(repositoryClass=AreaRepository::class)
 */
class Area
{

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\NotNull(message="Gebietsname darf nicht leer sein!")
     * @Assert\Length(
     *      min = 2,
     *      minMessage = "Gebietsname sollte mehr als zwei Zeichen enthalten!",
     * )
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\NotNull(message="URL darf nicht leer sein und darf keine Umlaute enthalten!")
     * @Assert\Length(
     *      min = 2,
     *      minMessage = "URL sollte mehr als zwei Zeichen enthalten!",
     * )
     */
    private $slug;

    /**
     * @ORM\Column(type="string", length=25)
     */
    private $orientation;

    /**
     * @ORM\OneToMany(targetEntity=Rock::class, mappedBy="area", fetch="EXTRA_LAZY")
     */
    private $rocks;

     /**
     * @ORM\OneToMany(targetEntity=Routes::class, mappedBy="area", fetch="EXTRA_LAZY")
     */
    private $routes;

    /**
     * @ORM\Column(type="smallint", nullable=true)
     */
    private $sequence;

    /**
     * @ORM\Column(type="smallint")
     */
    private $online;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $image;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $headerImage;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $lat;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $lng;

    /**
     * @ORM\Column(type="smallint", nullable=true)
     */
    private $zoom;

    public function __construct()
    {
        $this->rocks = new ArrayCollection();
        $this->routes = new ArrayCollection();
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

    public function __toString(){
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
