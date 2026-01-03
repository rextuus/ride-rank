<?php

namespace App\Common\Entity\Trait;

use Doctrine\ORM\Mapping as ORM;

trait IdentTrait
{
    #[ORM\Column(length: 255, unique: true, nullable: true)]
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

    public function __toString(): string
    {
        return $this->getName() ?: '-';
    }
}
