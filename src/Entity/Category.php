<?php

namespace App\Entity;

use App\Common\Entity\Trait\CreateDateTrait;
use App\Common\Entity\Trait\IdentTrait;
use App\Common\Entity\Trait\RcdbEntityTrait;
use App\Repository\CategoryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CategoryRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Category
{
    use IdentTrait;
    use CreateDateTrait;
    use RcdbEntityTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * @var Collection<int, Coaster>
     */
    #[ORM\ManyToMany(targetEntity: Coaster::class, mappedBy: 'categories')]
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
            $coaster->addCategory($this);
        }

        return $this;
    }

    public function removeCoaster(Coaster $coaster): static
    {
        if ($this->coasters->removeElement($coaster)) {
            $coaster->removeCategory($this);
        }

        return $this;
    }
}
