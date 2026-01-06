<?php

namespace App\Entity;

use App\Repository\UserCoasterAffinityRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserCoasterAffinityRepository::class)]
#[ORM\Table(name: 'user_coaster_affinity')]
#[ORM\UniqueConstraint(name: 'uniq_user_coaster', columns: ['user_id', 'coaster_id'])]
#[ORM\Index(name: 'idx_user_coaster_affinity_user', columns: ['user_id'])]
#[ORM\Index(name: 'idx_user_coaster_affinity_coaster', columns: ['coaster_id'])]
class UserCoasterAffinity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: Coaster::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Coaster $coaster = null;

    #[ORM\Column(options: ['default' => 0])]
    private int $exposureCount = 0;

    #[ORM\Column(options: ['default' => 0])]
    private int $winCount = 0;

    #[ORM\Column(options: ['default' => 0])]
    private int $lossCount = 0;

    #[ORM\Column(options: ['default' => 0.0])]
    private float $confidenceScore = 0.0;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $lastSeenAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
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

    public function getExposureCount(): int
    {
        return $this->exposureCount;
    }

    public function setExposureCount(int $exposureCount): static
    {
        $this->exposureCount = $exposureCount;

        return $this;
    }

    public function getWinCount(): int
    {
        return $this->winCount;
    }

    public function setWinCount(int $winCount): static
    {
        $this->winCount = $winCount;

        return $this;
    }

    public function getLossCount(): int
    {
        return $this->lossCount;
    }

    public function setLossCount(int $lossCount): static
    {
        $this->lossCount = $lossCount;

        return $this;
    }

    public function getConfidenceScore(): float
    {
        return $this->confidenceScore;
    }

    public function setConfidenceScore(float $confidenceScore): static
    {
        $this->confidenceScore = $confidenceScore;

        return $this;
    }

    public function getLastSeenAt(): ?\DateTimeImmutable
    {
        return $this->lastSeenAt;
    }

    public function setLastSeenAt(?\DateTimeImmutable $lastSeenAt): static
    {
        $this->lastSeenAt = $lastSeenAt;

        return $this;
    }
}
