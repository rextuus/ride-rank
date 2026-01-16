<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\PlayerCoasterAffinityRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PlayerCoasterAffinityRepository::class)]
#[ORM\Table(name: 'player_coaster_affinity')]
#[ORM\UniqueConstraint(
    name: 'uniq_affinity_player_coaster',
    columns: ['player_id', 'coaster_id']
)]
#[ORM\Index(
    name: 'idx_player_coaster_affinity_player',
    columns: ['player_id']
)]
#[ORM\Index(
    name: 'idx_player_coaster_affinity_coaster',
    columns: ['coaster_id']
)]
class PlayerCoasterAffinity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Player::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Player $player;

    #[ORM\ManyToOne(targetEntity: Coaster::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Coaster $coaster;

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

    public function __construct(Player $player, Coaster $coaster)
    {
        $this->player = $player;
        $this->coaster = $coaster;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPlayer(): Player
    {
        return $this->player;
    }

    public function getCoaster(): Coaster
    {
        return $this->coaster;
    }

    public function getExposureCount(): int
    {
        return $this->exposureCount;
    }

    public function incrementExposure(): void
    {
        ++$this->exposureCount;
        $this->lastSeenAt = new \DateTimeImmutable();
    }

    public function getWinCount(): int
    {
        return $this->winCount;
    }

    public function incrementWins(): void
    {
        ++$this->winCount;
    }

    public function getLossCount(): int
    {
        return $this->lossCount;
    }

    public function incrementLosses(): void
    {
        ++$this->lossCount;
    }

    public function getConfidenceScore(): float
    {
        return $this->confidenceScore;
    }

    public function setConfidenceScore(float $confidenceScore): void
    {
        $this->confidenceScore = $confidenceScore;
    }

    public function getLastSeenAt(): ?\DateTimeImmutable
    {
        return $this->lastSeenAt;
    }

    public function setId(?int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function setPlayer(Player $player): self
    {
        $this->player = $player;
        return $this;
    }

    public function setCoaster(Coaster $coaster): self
    {
        $this->coaster = $coaster;
        return $this;
    }

    public function setExposureCount(int $exposureCount): self
    {
        $this->exposureCount = $exposureCount;
        return $this;
    }

    public function setWinCount(int $winCount): self
    {
        $this->winCount = $winCount;
        return $this;
    }

    public function setLossCount(int $lossCount): self
    {
        $this->lossCount = $lossCount;
        return $this;
    }

    public function setLastSeenAt(?\DateTimeImmutable $lastSeenAt): self
    {
        $this->lastSeenAt = $lastSeenAt;
        return $this;
    }
}
