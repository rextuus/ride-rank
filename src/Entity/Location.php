<?php

namespace App\Entity;

use App\Common\Entity\Trait\CreateDateTrait;
use App\Common\Entity\Trait\IdentTrait;
use App\Common\Entity\Trait\RcdbEntityTrait;
use App\Repository\LocationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use App\Common\Entity\Enum\LocationType;

#[ORM\Entity(repositoryClass: LocationRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Location
{
    use IdentTrait;
    use RcdbEntityTrait;
    use CreateDateTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(enumType: LocationType::class, options: ['default' => LocationType::NOT_DETERMINED])]
    private ?LocationType $type = LocationType::NOT_DETERMINED;

    /**
     * @var Collection<int, Coaster>
     */
    #[ORM\ManyToMany(targetEntity: Coaster::class, mappedBy: 'locations')]
    private Collection $coasters;

    /**
     * @var Collection<int, Train>
     */
    #[ORM\OneToMany(targetEntity: Train::class, mappedBy: 'restraint')]
    private Collection $trains;

    public function __construct()
    {
        $this->coasters = new ArrayCollection();
        $this->trains = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getType(): ?LocationType
    {
        return $this->type;
    }

    public function setType(LocationType $type): static
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return Collection<int, Coaster>
     */
    public function getCoasters(): Collection
    {
        return $this->coasters;
    }

    public function addCoaster(Coaster $coaster): static
    {
        if (!$this->coasters->contains($coaster)) {
            $this->coasters->add($coaster);
            $coaster->addLocation($this);
        }

        return $this;
    }

    public function removeCoaster(Coaster $coaster): static
    {
        if ($this->coasters->removeElement($coaster)) {
            $coaster->removeLocation($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, Train>
     */
    public function getTrains(): Collection
    {
        return $this->trains;
    }

    public function addTrain(Train $train): static
    {
        if (!$this->trains->contains($train)) {
            $this->trains->add($train);
            $train->setRestraint($this);
        }

        return $this;
    }

    public function removeTrain(Train $train): static
    {
        if ($this->trains->removeElement($train)) {
            // set the owning side to null (unless already changed)
            if ($train->getRestraint() === $this) {
                $train->setRestraint(null);
            }
        }

        return $this;
    }
}
