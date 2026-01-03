<?php

namespace App\Entity;

use App\Common\Entity\Trait\CreateDateTrait;
use App\Repository\TrackRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TrackRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Track
{
    use CreateDateTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(nullable: true)]
    private ?float $length = null;

    #[ORM\Column(nullable: true)]
    private ?float $height = null;

    #[ORM\Column(name: '`drop`', nullable: true)]
    private ?float $drop = null;

    #[ORM\Column(nullable: true)]
    private ?float $speed = null;

    #[ORM\Column(nullable: true)]
    private ?int $inversions = null;

    #[ORM\Column(nullable: true)]
    private ?int $duration = null;

    #[ORM\Column(nullable: true)]
    private ?int $verticalAngle = null;

    #[ORM\OneToOne(mappedBy: 'track', cascade: ['persist', 'remove'])]
    private ?Coaster $coaster = null;

    /**
     * @var Collection<int, TrackElement>
     */
    #[ORM\OneToMany(targetEntity: TrackElement::class, mappedBy: 'track', orphanRemoval: true, cascade: ['persist', 'remove'])]
    private Collection $elements;

    public function __construct()
    {
        $this->elements = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLength(): ?float
    {
        return $this->length;
    }

    public function setLength(?float $length): static
    {
        $this->length = $length;

        return $this;
    }

    public function getHeight(): ?float
    {
        return $this->height;
    }

    public function setHeight(?float $height): static
    {
        $this->height = $height;

        return $this;
    }

    public function getDrop(): ?float
    {
        return $this->drop;
    }

    public function setDrop(?float $drop): static
    {
        $this->drop = $drop;

        return $this;
    }

    public function getSpeed(): ?float
    {
        return $this->speed;
    }

    public function setSpeed(?float $speed): static
    {
        $this->speed = $speed;

        return $this;
    }

    public function getInversions(): ?int
    {
        return $this->inversions;
    }

    public function setInversions(?int $inversions): static
    {
        $this->inversions = $inversions;

        return $this;
    }

    public function getDuration(): ?int
    {
        return $this->duration;
    }

    public function setDuration(?int $duration): static
    {
        $this->duration = $duration;

        return $this;
    }

    public function getVerticalAngle(): ?int
    {
        return $this->verticalAngle;
    }

    public function setVerticalAngle(?int $verticalAngle): static
    {
        $this->verticalAngle = $verticalAngle;

        return $this;
    }

    public function getCoaster(): ?Coaster
    {
        return $this->coaster;
    }

    public function setCoaster(?Coaster $coaster): static
    {
        // unset the owning side of the relation if necessary
        if ($coaster === null && $this->coaster !== null) {
            $this->coaster->setTrack(null);
        }

        // set the owning side of the relation if necessary
        if ($coaster !== null && $coaster->getTrack() !== $this) {
            $coaster->setTrack($this);
        }

        $this->coaster = $coaster;

        return $this;
    }

    /**
     * @return Collection<int, TrackElement>
     */
    public function getElements(): Collection
    {
        return $this->elements;
    }

    public function addElement(TrackElement $element): static
    {
        if (!$this->elements->contains($element)) {
            $this->elements->add($element);
            $element->setTrack($this);
        }

        return $this;
    }

    public function removeElement(TrackElement $element): static
    {
        if ($this->elements->removeElement($element)) {
            // set the owning side to null (unless already changed)
            if ($element->getTrack() === $this) {
                $element->setTrack(null);
            }
        }

        return $this;
    }
}
