<?php

namespace App\Entity;

use App\Common\Entity\Trait\CreateDateTrait;
use App\Common\Entity\Trait\IdentTrait;
use App\Common\Entity\Trait\RcdbEntityTrait;
use App\Repository\TrackElementRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TrackElementRepository::class)]
#[ORM\HasLifecycleCallbacks]
class TrackElement
{
    use IdentTrait;
    use RcdbEntityTrait;
    use CreateDateTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * @var Collection<int, Track>
     */
    #[ORM\ManyToMany(targetEntity: Track::class, mappedBy: 'elements')]
    private Collection $tracks;

    public function __construct()
    {
        $this->tracks = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return Collection<int, Track>
     */
    public function getTracks(): Collection
    {
        return $this->tracks;
    }

    public function addTrack(Track $track): static
    {
        if (!$this->tracks->contains($track)) {
            $this->tracks->add($track);
            $track->addElement($this);
        }

        return $this;
    }

    public function removeTrack(Track $track): static
    {
        if ($this->tracks->removeElement($track)) {
            $track->removeElement($this);
        }

        return $this;
    }
}
