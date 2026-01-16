<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\PlayerRepository;
use App\Service\Player\PlayerExperienceLevel;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: PlayerRepository::class)]
#[ORM\Table(name: 'player')]
class Player
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $user = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $lastSeenAt;

    #[ORM\Column(length: 64, nullable: true)]
    private ?string $deviceHash = null;

    #[ORM\Column(type: 'boolean')]
    private bool $anonymous = true;

    /**
     * @var Collection<int, RiddenCoaster>
     */
    #[ORM\OneToMany(targetEntity: RiddenCoaster::class, mappedBy: 'player', orphanRemoval: true)]
    private Collection $riddenCoasters;

    #[ORM\Column(options: ['default' => 0])]
    private int $experience = 0;

    #[ORM\ManyToOne(targetEntity: Location::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Location $homeCountry = null;

    public function __construct()
    {
        $now = new \DateTimeImmutable();
        $this->createdAt = $now;
        $this->lastSeenAt = $now;
        $this->anonymous = true;
        $this->riddenCoasters = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function attachUser(User $user): void
    {
        $this->user = $user;
        $this->anonymous = false;
        $this->touch();
    }

    public function isAnonymous(): bool
    {
        return $this->anonymous;
    }

    public function touch(): void
    {
        $this->lastSeenAt = new \DateTimeImmutable();
    }

    public function getLastSeenAt(): \DateTimeImmutable
    {
        return $this->lastSeenAt;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getDeviceHash(): ?string
    {
        return $this->deviceHash;
    }

    public function setDeviceHash(?string $deviceHash): void
    {
        $this->deviceHash = $deviceHash;
    }

    /**
     * @return Collection<int, RiddenCoaster>
     */
    public function getRiddenCoasters(): Collection
    {
        return $this->riddenCoasters;
    }

    public function addRiddenCoaster(RiddenCoaster $riddenCoaster): static
    {
        if (!$this->riddenCoasters->contains($riddenCoaster)) {
            $this->riddenCoasters->add($riddenCoaster);
            $riddenCoaster->setPlayer($this);
        }

        return $this;
    }

    public function removeRiddenCoaster(RiddenCoaster $riddenCoaster): static
    {
        if ($this->riddenCoasters->removeElement($riddenCoaster)) {
            // set the owning side to null (unless already changed)
            if ($riddenCoaster->getPlayer() === $this) {
                // Since user is not nullable in RiddenCoaster, we rely on orphanRemoval: true which is set in OneToMany.
                // This means that removing the RiddenCoaster from the collection will automatically remove it from the database.
                // No need to explicitly set the user to null.
            }
        }

        return $this;
    }

    public function gainExperience(int $amount = 1): void
    {
        $this->experience += $amount;
    }

    public function reduceExperience(int $amount = 1): void
    {
        $this->experience -= $amount;
    }

    public function getExperience(): int
    {
        return $this->experience;
    }

    public function getExperienceLevel(): PlayerExperienceLevel
    {
        return match (true) {
            $this->experience <= 10  => PlayerExperienceLevel::NEWBIE,
            $this->experience <= 50  => PlayerExperienceLevel::LOCAL,
            $this->experience <= 150 => PlayerExperienceLevel::ENTHUSIAST,
            default                  => PlayerExperienceLevel::EXPERT,
        };
    }

    public function getHomeCountry(): ?Location
    {
        return $this->homeCountry;
    }

    public function setHomeCountry(?Location $country): void
    {
        $this->homeCountry = $country;
    }

    public function __toString(): string
    {
        $user = $this->getUser();
        if ($user) {
            return sprintf('%s (%s)', $user->getEmail(), $this->getId());
        }
        return sprintf('Anonymous (%s)', $this->getId());
    }
}
