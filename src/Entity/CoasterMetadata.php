<?php

namespace App\Entity;

use App\Repository\CoasterMetadataRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CoasterMetadataRepository::class)]
class CoasterMetadata
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Coaster::class, inversedBy: 'metadata', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?Coaster $coaster = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $images = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $statusDates = null;

    public function __construct(?Coaster $coaster = null)
    {
        $this->coaster = $coaster;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCoaster(): ?Coaster
    {
        return $this->coaster;
    }

    public function setCoaster(?Coaster $coaster): static
    {
        $this->coaster = $coaster;
        return $this;
    }

    public function getImages(): ?array
    {
        return $this->images;
    }

    public function setImages(?array $images): static
    {
        $this->images = $images;
        return $this;
    }

    public function getStatusDates(): ?array
    {
        return $this->statusDates;
    }

    public function setStatusDates(?array $statusDates): static
    {
        $this->statusDates = $statusDates;
        return $this;
    }
}