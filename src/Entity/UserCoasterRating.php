<?php

namespace App\Entity;

use App\Repository\UserCoasterRatingRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserCoasterRatingRepository::class)]
#[ORM\Table(
    name: 'user_coaster_rating',
    indexes: [
        new ORM\Index(name: 'idx_user_rating_user', columns: ['user_id']),
        new ORM\Index(name: 'idx_user_rating_rating', columns: ['rating'])
    ],
    uniqueConstraints: [
        new ORM\UniqueConstraint(
            name: 'uniq_user_coaster',
            columns: ['user_id', 'coaster_id']
        )
    ]
)]
class UserCoasterRating
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\ManyToOne(targetEntity: Coaster::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Coaster $coaster;

    #[ORM\Column(options: ['default' => 1200])]
    private float $rating = 1200.0;

    #[ORM\Column(options: ['default' => 0])]
    private int $gamesPlayed = 0;

    #[ORM\Column(options: ['default' => 0])]
    private int $wins = 0;

    #[ORM\Column(options: ['default' => 0])]
    private int $losses = 0;

    public function __construct(User $user, Coaster $coaster)
    {
        $this->user = $user;
        $this->coaster = $coaster;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getCoaster(): Coaster
    {
        return $this->coaster;
    }

    public function getRating(): float
    {
        return $this->rating;
    }

    public function setRating(float $rating): static
    {
        $this->rating = $rating;
        return $this;
    }

    public function getGamesPlayed(): int
    {
        return $this->gamesPlayed;
    }

    public function incrementGamesPlayed(): static
    {
        $this->gamesPlayed = $this->gamesPlayed + 1;

        return $this;
    }

    public function getWins(): int
    {
        return $this->wins;
    }

    public function incrementWins(): static
    {
        $this->wins = $this->wins + 1;

        return $this;
    }

    public function getLosses(): int
    {
        return $this->losses;
    }

    public function incrementLosses(): static
    {
        ++$this->losses;
        return $this;
    }
}
