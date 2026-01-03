<?php

namespace App\Entity;

use App\Common\Entity\Trait\CreateDateTrait;
use App\Common\Entity\Trait\IdentTrait;
use App\Common\Entity\Trait\RcdbEntityTrait;
use App\Repository\TrackElementRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TrackElementRepository::class)]
#[ORM\HasLifecycleCallbacks]
class TrackElement
{
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $ident = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $name = null;

    public function getIdent(): ?string
    {
        return $this->ident;
    }

    public function setIdent(string $ident): static
    {
        $this->ident = $ident;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    use RcdbEntityTrait;
    use CreateDateTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'elements')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Track $track = null;

    #[ORM\Column(nullable: true)]
    private ?int $position = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTrack(): ?Track
    {
        return $this->track;
    }

    public function setTrack(?Track $track): static
    {
        $this->track = $track;

        return $this;
    }

    public function getPosition(): ?int
    {
        return $this->position;
    }

    public function setPosition(?int $position): static
    {
        $this->position = $position;

        return $this;
    }
}
