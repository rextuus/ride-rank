<?php

namespace App\Common\Entity\Trait;

use DateTime;
use Doctrine\ORM\Mapping as ORM;

trait CreateDateTrait
{

    #[ORM\Column]
    private ?DateTime $created = null;

    #[ORM\Column(nullable: true)]
    private ?DateTime $edited = null;

    #[ORM\PrePersist]
    public function setCreatedValue(): void
    {
        $this->created = new DateTime();
    }

    #[ORM\PreUpdate]
    public function setEditedValue(): void
    {
        $this->edited = new DateTime();
    }

    public function getCreated(): ?DateTime
    {
        return $this->created;
    }

    public function setCreated(?DateTime $created): self
    {
        $this->created = $created;

        return $this;
    }

    public function getEdited(): ?DateTime
    {
        return $this->edited;
    }

    public function setEdited(?DateTime $edited): self
    {
        $this->edited = $edited;

        return $this;
    }
}
