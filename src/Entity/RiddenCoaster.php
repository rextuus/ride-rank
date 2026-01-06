<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\RiddenCoasterRepository;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RiddenCoasterRepository::class)]
class RiddenCoaster
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'riddenCoasters')]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    #[ORM\ManyToOne(targetEntity: Coaster::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Coaster $coaster;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $riddenAt;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getCoaster(): Coaster
    {
        return $this->coaster;
    }

    public function setCoaster(Coaster $coaster): static
    {
        $this->coaster = $coaster;

        return $this;
    }

    public function getRiddenAt(): DateTimeImmutable
    {
        return $this->riddenAt;
    }

    public function setRiddenAt(DateTimeImmutable $riddenAt): static
    {
        $this->riddenAt = $riddenAt;

        return $this;
    }
}
