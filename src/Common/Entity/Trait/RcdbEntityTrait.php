<?php

namespace App\Common\Entity\Trait;

use Doctrine\ORM\Mapping as ORM;

trait RcdbEntityTrait
{

    #[ORM\Column(nullable: true)]
    private ?int $rcdbId = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $rcdbUrl = null;

    public function getRcdbId(): ?int
    {
        return $this->rcdbId;
    }

    public function setRcdbId(int $rcdbId): static
    {
        $this->rcdbId = $rcdbId;

        return $this;
    }

    public function getRcdbUrl(): ?string
    {
        return $this->rcdbUrl;
    }

    public function setRcdbUrl(?string $rcdbUrl): static
    {
        $this->rcdbUrl = $rcdbUrl;

        return $this;
    }
}
