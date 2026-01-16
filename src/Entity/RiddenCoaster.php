<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\RiddenCoasterRepository;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RiddenCoasterRepository::class)]
#[ORM\UniqueConstraint(
    name: 'uniq_ridden_player_coaster',
    columns: ['player_id', 'coaster_id']
)]
class RiddenCoaster
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Player::class, inversedBy: 'riddenCoasters')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Player $player;

    #[ORM\ManyToOne(targetEntity: Coaster::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Coaster $coaster;

    #[ORM\Column(type: 'boolean')]
    private bool $ridden;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPlayer(): Player
    {
        return $this->player;
    }

    public function setPlayer(Player $player): static
    {
        $this->player = $player;
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

    public function isRidden(): bool
    {
        return $this->ridden;
    }

    public function setRidden(bool $ridden): static
    {
        $this->ridden = $ridden;
        return $this;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }
}
