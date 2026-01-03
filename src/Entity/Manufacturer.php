<?php

namespace App\Entity;

use App\Common\Entity\Trait\CreateDateTrait;
use App\Common\Entity\Trait\IdentTrait;
use App\Common\Entity\Trait\RcdbEntityTrait;
use App\Repository\ManufacturerRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ManufacturerRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Manufacturer
{
    use IdentTrait;
    use RcdbEntityTrait;
    use CreateDateTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * @var Collection<int, Coaster>
     */
    #[ORM\OneToMany(targetEntity: Coaster::class, mappedBy: 'manufacturer')]
    private Collection $coasters;

    public function __construct()
    {
        $this->coasters = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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
            $coaster->setManufacturer($this);
        }

        return $this;
    }

    public function removeCoaster(Coaster $coaster): static
    {
        if ($this->coasters->removeElement($coaster)) {
            // set the owning side to null (unless already changed)
            if ($coaster->getManufacturer() === $this) {
                $coaster->setManufacturer(null);
            }
        }

        return $this;
    }
}
