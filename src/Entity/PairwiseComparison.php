<?php

namespace App\Entity;

use App\Repository\PairwiseComparisonRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PairwiseComparisonRepository::class)]
#[ORM\Index(name: 'idx_pairwise_comparison_user', columns: ['user_id'])]
#[ORM\Index(name: 'idx_pairwise_comparison_coaster_a', columns: ['coaster_a_id'])]
#[ORM\Index(name: 'idx_pairwise_comparison_coaster_b', columns: ['coaster_b_id'])]
#[ORM\Index(name: 'idx_pairwise_comparison_created_at', columns: ['created_at'])]
class PairwiseComparison
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Coaster::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Coaster $coasterA = null;

    #[ORM\ManyToOne(targetEntity: Coaster::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Coaster $coasterB = null;

    #[ORM\ManyToOne(targetEntity: Coaster::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Coaster $winner = null;

    #[ORM\ManyToOne(targetEntity: Coaster::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Coaster $loser = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $user = null;

    #[ORM\Column(nullable: true)]
    private ?int $responseTimeMs = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCoasterA(): ?Coaster
    {
        return $this->coasterA;
    }

    public function setCoasterA(?Coaster $coasterA): static
    {
        $this->coasterA = $coasterA;

        return $this;
    }

    public function getCoasterB(): ?Coaster
    {
        return $this->coasterB;
    }

    public function setCoasterB(?Coaster $coasterB): static
    {
        $this->coasterB = $coasterB;

        return $this;
    }

    public function getWinner(): ?Coaster
    {
        return $this->winner;
    }

    public function setWinner(?Coaster $winner): static
    {
        $this->winner = $winner;

        return $this;
    }

    public function getLoser(): ?Coaster
    {
        return $this->loser;
    }

    public function setLoser(?Coaster $loser): static
    {
        $this->loser = $loser;

        return $this;
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

    public function getResponseTimeMs(): ?int
    {
        return $this->responseTimeMs;
    }

    public function setResponseTimeMs(?int $responseTimeMs): static
    {
        $this->responseTimeMs = $responseTimeMs;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }
}
