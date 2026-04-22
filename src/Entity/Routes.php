<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Repository\RoutesRepository;
use App\Service\GradeTranslationService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: RoutesRepository::class)]
#[ApiResource(
    operations: [
        new Get(normalizationContext: ['groups' => ['route:read']]),
        new GetCollection(normalizationContext: ['groups' => ['route:read']]),
    ]
)]
class Routes
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['route:read', 'comment:read', 'photo:read', 'video:read'])]
    private ?int $id = null;

    #[Assert\NotBlank(message: 'Routenname darf nicht leer sein!')]
    #[Assert\Length(minMessage: 'Routenname sollte mehr als zwei Zeichen enthalten!', min: 2)]
    #[ORM\Column(type: Types::STRING, length: 255)]
    #[Groups(['route:read', 'comment:read', 'photo:read', 'video:read'])]
    private ?string $name = null;

    #[ORM\ManyToOne(inversedBy: 'routes', fetch: 'EXTRA_LAZY')]
    #[Groups(['route:read'])]
    private ?Area $area = null;

    #[ORM\ManyToOne(inversedBy: 'routes', fetch: 'EXTRA_LAZY')]
    #[Groups(['route:read'])]
    private ?Rock $rock = null;

    #[ORM\Column(type: Types::STRING, length: 20, nullable: true)]
    #[Groups(['route:read', 'comment:read'])]
    private ?string $grade = null;

    #[ORM\Column(type: Types::BOOLEAN, nullable: true)]
    private bool $climbed = false;

    #[ORM\Column(type: Types::STRING, length: 100, nullable: true)]
    #[Groups(['route:read'])]
    private ?string $firstAscent = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    #[Groups(['route:read'])]
    protected ?int $yearFirstAscent = null;

    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    #[Groups(['route:read'])]
    private ?int $protection = null;

    #[ORM\Column(type: Types::STRING, length: 100, nullable: true)]
    #[Groups(['route:read'])]
    private ?string $scale = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    #[Groups(['route:read'])]
    protected ?int $gradeNo = null;

    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    #[Groups(['route:read'])]
    private ?int $rating = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    #[Groups(['route:read'])]
    protected ?int $topoId = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    #[Groups(['route:read'])]
    protected ?int $nr = null;

    #[ORM\OneToMany(mappedBy: 'route', targetEntity: Comment::class)]
    private Collection $comments;

    #[ORM\Column(nullable: true)]
    #[Groups(['route:read'])]
    private ?bool $rockQuality = null;

    /**
     * Climbing style(s), e.g. ["sport", "slab", "crack"]
     *
     * @var array<string>|null
     */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    #[Groups(['route:read'])]
    private ?array $climbingStyle = null;

    public function __construct()
    {
        $this->comments = new ArrayCollection();
    }

    public function __toString(): string
    {
        $rockName = $this->getRock() instanceof \App\Entity\Rock ? $this->getRock()->getName() : 'No rock';
        $areaName = $this->getArea() instanceof \App\Entity\Area ? $this->getArea()->getName() : 'No area';
        $routeName = $this->getName() ? $this->getName() : 'No route';

        return $routeName . ' - ' . $rockName . ' - ' . $areaName;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): self
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

    public function getRock(): ?Rock
    {
        return $this->rock;
    }

    public function setRock(?Rock $rock): self
    {
        $this->rock = $rock;

        return $this;
    }

    public function getGrade(): ?string
    {
        return $this->grade;
    }

    public function setGrade(?string $grade): self
    {
        $this->grade = $grade;
        
        // Automatically set the numeric grade
        $this->setGradeNoFromGrade($grade);

        return $this;
    }

    public function getClimbed(): ?bool
    {
        return $this->climbed;
    }

    public function setClimbed(?bool $climbed): self
    {
        $this->climbed = $climbed;

        return $this;
    }

    public function getFirstAscent(): ?string
    {
        return $this->firstAscent;
    }

    public function setFirstAscent(?string $firstAscent): self
    {
        $this->firstAscent = $firstAscent;

        return $this;
    }

    public function getYearFirstAscent(): ?int
    {
        return $this->yearFirstAscent;
    }

    public function setYearFirstAscent(?int $yearFirstAscent): self
    {
        $this->yearFirstAscent = $yearFirstAscent;

        return $this;
    }

    public function getProtection(): ?int
    {
        return $this->protection;
    }

    public function setProtection(?int $protection): self
    {
        $this->protection = $protection;

        return $this;
    }

    public function getScale(): ?string
    {
        return $this->scale;
    }

    public function setScale(string $scale): self
    {
        $this->scale = $scale;

        return $this;
    }

    public function getGradeNo(): ?int
    {
        return $this->gradeNo;
    }

    public function setGradeNo(?int $gradeNo): self
    {
        $this->gradeNo = $gradeNo;

        return $this;
    }

    public function getRating(): ?int
    {
        return $this->rating;
    }

    public function setRating(?int $rating): self
    {
        $this->rating = $rating;

        return $this;
    }

    public function getTopoId(): ?int
    {
        return $this->topoId;
    }

    public function setTopoId(?int $topoId): self
    {
        $this->topoId = $topoId;

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

    /**
     * @return Collection<int, Comment>
     */
    public function getComments(): Collection
    {
        return $this->comments;
    }

    public function addComment(Comment $comment): static
    {
        if (!$this->comments->contains($comment)) {
            $this->comments->add($comment);
            $comment->setRoute($this);
        }

        return $this;
    }

    public function removeComment(Comment $comment): static
    {
        // set the owning side to null (unless already changed)
        if ($this->comments->removeElement($comment) && $comment->getRoute() === $this) {
            $comment->setRoute(null);
        }

        return $this;
    }

    public function isRockQuality(): ?bool
    {
        return $this->rockQuality;
    }

    public function setRockQuality(?bool $rockQuality): static
    {
        $this->rockQuality = $rockQuality;

        return $this;
    }

    /**
     * @return array<string>|null
     */
    public function getClimbingStyle(): ?array
    {
        return $this->climbingStyle;
    }

    /**
     * @param array<string>|null $climbingStyle
     */
    public function setClimbingStyle(?array $climbingStyle): static
    {
        $this->climbingStyle = $climbingStyle;

        return $this;
    }

    /**
     * Set the numeric grade based on the string grade
     */
    public function setGradeNoFromGrade(?string $grade): void
    {
        if ($grade === null || $grade === '') {
            $this->gradeNo = null;
            return;
        }

        $this->gradeNo = GradeTranslationService::gradeToMappedNumber($grade);
    }

    /**
     * Automatically set gradeNo before persisting
     */
    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $this->setGradeNoFromGrade($this->grade);
    }

    /**
     * Automatically set gradeNo before updating
     */
    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->setGradeNoFromGrade($this->grade);
    }
}
