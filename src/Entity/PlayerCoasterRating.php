<?php

namespace App\Entity;

use App\Repository\PlayerCoasterRatingRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PlayerCoasterRatingRepository::class)]
#[ORM\Table(
    name: 'player_coaster_rating',
    indexes: [
        new ORM\Index(name: 'idx_player_rating_player', columns: ['player_id']),
        new ORM\Index(name: 'idx_player_rating_rating', columns: ['rating'])
    ],
    uniqueConstraints: [
        new ORM\UniqueConstraint(
            name: 'uniq_player_coaster',
            columns: ['player_id', 'coaster_id']
        )
    ]
)]
class PlayerCoasterRating
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

    #[ORM\Column(options: ['default' => 1200])]
    private float $rating = 1200.0;

    #[ORM\Column(options: ['default' => 0])]
    private int $presented = 0;

    #[ORM\Column(options: ['default' => 0])]
    private int $skipped = 0;

    #[ORM\Column(options: ['default' => 0])]
    private int $gamesPlayed = 0;

    #[ORM\Column(options: ['default' => 0])]
    private int $wins = 0;

    #[ORM\Column(options: ['default' => 0])]
    private int $losses = 0;

    public function __construct(Player $player, Coaster $coaster)
    {
        $this->player = $player;
        $this->coaster = $coaster;
    }

    // --- Basic getters ---
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

    public function getRating(): float
    {
        return $this->rating;
    }

    public function getGamesPlayed(): int
    {
        return $this->gamesPlayed;
    }

    public function getWins(): int
    {
        return $this->wins;
    }

    public function getLosses(): int
    {
        return $this->losses;
    }

    // --- Setters ---
    public function setRating(float $rating): void
    {
        $this->rating = $rating;
    }

    public function setGamesPlayed(int $games): void
    {
        $this->gamesPlayed = $games;
    }

    public function setWins(int $wins): void
    {
        $this->wins = $wins;
    }

    public function setLosses(int $losses): void
    {
        $this->losses = $losses;
    }

    public function getPresented(): int
    {
        return $this->presented;
    }

    public function setPresented(int $presented): self
    {
        $this->presented = $presented;

        return $this;
    }

    public function getSkipped(): int
    {
        return $this->skipped;
    }

    public function setSkipped(int $skipped): self
    {
        $this->skipped = $skipped;

        return $this;
    }

    // --- Increment helpers ---
    public function incrementGamesPlayed(): void
    {
        ++$this->gamesPlayed;
    }

    public function incrementWins(): void
    {
        ++$this->wins;
    }

    public function incrementLosses(): void
    {
        ++$this->losses;
    }

    public function incrementPresented(): void
    {
        ++$this->presented;
    }

    public function incrementSkipped(): void
    {
        ++$this->skipped;
    }
}
