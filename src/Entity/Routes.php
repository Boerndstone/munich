<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Repository\RoutesRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: RoutesRepository::class)]
#[ApiResource(
    operations: [
        new Get(),
        new GetCollection(),
    ]
)]
class Routes
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[Assert\NotBlank(message: 'Routenname darf nicht leer sein!')]
    #[Assert\Length(minMessage: 'Routenname sollte mehr als zwei Zeichen enthalten!', min: 2)]
    #[ORM\Column(type: Types::STRING, length: 255)]
    private ?string $name = null;

    #[ORM\ManyToOne(inversedBy: 'routes', fetch: 'EXTRA_LAZY')]
    private ?Area $area = null;

    #[ORM\ManyToOne(inversedBy: 'routes', fetch: 'EXTRA_LAZY')]
    private ?Rock $rock = null;

    #[ORM\Column(type: Types::STRING, length: 20, nullable: true)]
    private ?string $grade = null;

    #[ORM\Column(type: Types::BOOLEAN, nullable: true)]
    private bool $climbed = false;

    #[ORM\Column(type: Types::STRING, length: 100, nullable: true)]
    private ?string $firstAscent = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    protected ?int $yearFirstAscent = null;

    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    private ?int $protection = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: Types::STRING, length: 100, nullable: true)]
    private ?string $scale = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    protected ?int $gradeNo = null;

    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    private ?int $rating = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    protected ?int $topoId = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    protected ?int $nr = null;

    #[ORM\ManyToMany(targetEntity: ClimbedRoutes::class, mappedBy: 'ManyToMany')]
    private Collection $climbedRoutes;

    #[ORM\ManyToOne(inversedBy: 'realtion')]
    private ?FirstAscencionist $relatesToRoute = null;

    #[ORM\OneToMany(mappedBy: 'route', targetEntity: Comment::class)]
    private Collection $comments;

    #[ORM\Column(nullable: true)]
    private ?bool $rockQuality = null;

    public function __construct()
    {
        $this->climbedRoutes = new ArrayCollection();
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

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
     * @return Collection<int, ClimbedRoutes>
     */
    public function getClimbedRoutes(): Collection
    {
        return $this->climbedRoutes;
    }

    public function addClimbedRoute(ClimbedRoutes $climbedRoute): self
    {
        if (!$this->climbedRoutes->contains($climbedRoute)) {
            $this->climbedRoutes->add($climbedRoute);
            $climbedRoute->addManyToMany($this);
        }

        return $this;
    }

    public function removeClimbedRoute(ClimbedRoutes $climbedRoute): self
    {
        if ($this->climbedRoutes->removeElement($climbedRoute)) {
            $climbedRoute->removeManyToMany($this);
        }

        return $this;
    }

    public function getRelatesToRoute(): ?FirstAscencionist
    {
        return $this->relatesToRoute;
    }

    public function setRelatesToRoute(?FirstAscencionist $relatesToRoute): static
    {
        $this->relatesToRoute = $relatesToRoute;

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
     * Set the numeric grade based on the string grade
     */
    public function setGradeNoFromGrade(?string $grade): void
    {
        if ($grade === null || $grade === '') {
            $this->gradeNo = null;
            return;
        }

        $gradeMapping = [
            '0' => 500,
            '1' => 1,
            '2-' => 2,
            '2' => 3,
            '2+' => 4,
            '3-' => 5,
            '3' => 6,
            '3+' => 7,
            '4-' => 8,
            '4' => 9,
            '4a' => 10,
            '4b' => 11,
            '4c' => 12,
            '4c+' => 13,
            '4+' => 10,
            '5-' => 11,
            '5' => 12,
            '5/5+' => 13,
            '5+' => 14,
            '5+/6-' => 15,
            '5a' => 14,
            '5a+' => 15,
            '5b' => 16,
            '5b+' => 17,
            '5c' => 18,
            '5c+' => 19,
            '6-' => 16,
            '6-/6' => 17,
            '6' => 18,
            '6/6+' => 19,
            '6+' => 20,
            '6+/7-' => 21,
            '6a' => 20,
            '6a/6a+' => 21,
            '6a+' => 22,
            '6a+/6b' => 23,
            '6b' => 24,
            '6b/6b+' => 25,
            '6b+' => 27,
            '6c' => 28,
            '6c+' => 30,
            '6c+/7a' => 31,
            '7-' => 22,
            '7-/7' => 23,
            '7' => 24,
            '7/7+' => 25,
            '7+' => 27,
            '7+/8-' => 28,
            '7a' => 32,
            '7a/7a+' => 33,
            '7a+' => 35,
            '7b' => 36,
            '7b+' => 37,
            '7b+/7c' => 39,
            '7c' => 40,
            '7c/7c+' => 41,
            '7c+' => 43,
            '8-' => 30,
            '8-/8' => 31,
            '8' => 32,
            '8/8+' => 33,
            '8+' => 35,
            '8+/9-' => 36,
            '8a' => 44,
            '8a/8a+' => 45,
            '8a+' => 46,
            '8a+/8b' => 47,
            '8b' => 48,
            '8b/8b+' => 50,
            '8b+' => 51,
            '8b+/8c' => 52,
            '8c' => 54,
            '8c+' => 55,
            '8c+/9a' => 56,
            '9-' => 37,
            '9-/9' => 39,
            '9' => 40,
            '9/9+' => 41,
            '9+' => 43,
            '9+/10-' => 44,
            '9a' => 57,
            '9a/9a+' => 58,
            '9a+' => 59,
            '10-' => 46,
            '10-/10' => 47,
            '10' => 48,
            '10/10+' => 50,
            '10+' => 51,
            '10+/11-' => 52,
            '11-' => 54,
            '11-/11' => 55,
            '11' => 57,
        ];

        $this->gradeNo = $gradeMapping[$grade] ?? null;
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
