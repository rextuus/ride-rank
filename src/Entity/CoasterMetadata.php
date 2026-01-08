<?php

namespace App\Entity;

use App\Repository\CoasterMetadataRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CoasterMetadataRepository::class)]
class CoasterMetadata
{
    #[ORM\Id]
    #[ORM\OneToOne(targetEntity: Coaster::class, inversedBy: 'metadata', cascade: ['persist', 'remove'])]
    private ?Coaster $coaster = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $images = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $statusDates = null;

    public function __construct(?Coaster $coaster = null)
    {
        $this->coaster = $coaster;
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