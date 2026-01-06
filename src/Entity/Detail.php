<?php

namespace App\Entity;

use App\Common\Entity\Trait\CreateDateTrait;
use App\Common\Entity\Trait\IdentTrait;
use App\Enum\DetailType;
use App\Repository\DetailRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DetailRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Detail
{
    use CreateDateTrait;
    use IdentTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $content = null;

    #[ORM\Column(length: 255, nullable: true, enumType: DetailType::class)]
    private ?DetailType $type = null;

    /**
     * @var Collection<int, Coaster>
     */
    #[ORM\ManyToMany(targetEntity: Coaster::class, mappedBy: 'details')]
    private Collection $coasters;

    public function __construct()
    {
        $this->coasters = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): static
    {
        $this->content = $content;

        return $this;
    }

    public function getType(): ?DetailType
    {
        return $this->type;
    }

    public function setType(?DetailType $type): static
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
            $coaster->addDetail($this);
        }

        return $this;
    }

    public function removeCoaster(Coaster $coaster): static
    {
        if ($this->coasters->removeElement($coaster)) {
            $coaster->removeDetail($this);
        }

        return $this;
    }
}
