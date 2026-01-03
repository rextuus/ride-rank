<?php

namespace App\Entity;

use App\Common\Entity\Trait\CreateDateTrait;
use App\Repository\TrainRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TrainRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Train
{
    use CreateDateTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $arrangement = null;

    #[ORM\OneToOne(mappedBy: 'train', cascade: ['persist', 'remove'])]
    private ?Coaster $coaster = null;

    #[ORM\ManyToOne(inversedBy: 'trains')]
    private ?Location $restraint = null;

    #[ORM\ManyToOne]
    private ?Manufacturer $builtBy = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getArrangement(): ?string
    {
        return $this->arrangement;
    }

    public function setArrangement(?string $arrangement): static
    {
        $this->arrangement = $arrangement;

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
            $this->coaster->setTrain(null);
        }

        // set the owning side of the relation if necessary
        if ($coaster !== null && $coaster->getTrain() !== $this) {
            $coaster->setTrain($this);
        }

        $this->coaster = $coaster;

        return $this;
    }

    public function getRestraint(): ?Location
    {
        return $this->restraint;
    }

    public function setRestraint(?Location $restraint): static
    {
        $this->restraint = $restraint;

        return $this;
    }

    public function getBuiltBy(): ?Manufacturer
    {
        return $this->builtBy;
    }

    public function setBuiltBy(?Manufacturer $builtBy): static
    {
        $this->builtBy = $builtBy;

        return $this;
    }
}
