<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\ComparisonOutcome;
use App\Repository\PairwiseComparisonRepository;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PairwiseComparisonRepository::class)]
#[ORM\Table(name: 'pairwise_comparison')]
#[ORM\Index(name: 'idx_pairwise_comparison_player', columns: ['player_id'])]
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
    private Coaster $coasterA;

    #[ORM\ManyToOne(targetEntity: Coaster::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Coaster $coasterB;

    #[ORM\ManyToOne(targetEntity: Coaster::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Coaster $winner = null;

    #[ORM\ManyToOne(targetEntity: Coaster::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Coaster $loser = null;

    #[ORM\ManyToOne(targetEntity: Player::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Player $player;

    #[ORM\Column(nullable: true)]
    private ?int $responseTimeMs = null;

    #[ORM\Column]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(length: 10, options: ['default' => ComparisonOutcome::WIN])]
    private ComparisonOutcome $outcome = ComparisonOutcome::WIN;

    public function __construct()
    {
        $this->createdAt = new DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getCoasterA(): Coaster
    {
        return $this->coasterA;
    }

    public function setCoasterA(Coaster $coasterA): self
    {
        $this->coasterA = $coasterA;

        return $this;
    }

    public function getCoasterB(): Coaster
    {
        return $this->coasterB;
    }

    public function setCoasterB(Coaster $coasterB): self
    {
        $this->coasterB = $coasterB;

        return $this;
    }

    public function getWinner(): Coaster
    {
        return $this->winner;
    }

    public function setWinner(?Coaster $winner): self
    {
        $this->winner = $winner;

        return $this;
    }

    public function getLoser(): Coaster
    {
        return $this->loser;
    }

    public function setLoser(?Coaster $loser): self
    {
        $this->loser = $loser;

        return $this;
    }

    public function getPlayer(): Player
    {
        return $this->player;
    }

    public function setPlayer(Player $player): self
    {
        $this->player = $player;

        return $this;
    }

    public function getResponseTimeMs(): ?int
    {
        return $this->responseTimeMs;
    }

    public function setResponseTimeMs(?int $responseTimeMs): self
    {
        $this->responseTimeMs = $responseTimeMs;

        return $this;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getOutcome(): ComparisonOutcome
    {
        return $this->outcome;
    }

    public function setOutcome(ComparisonOutcome $outcome): self
    {
        $this->outcome = $outcome;
        return $this;
    }
}
