<?php

namespace App\Entity;

use App\Common\Entity\Enum\LocationType;
use App\Common\Entity\Trait\CreateDateTrait;
use App\Common\Entity\Trait\NonUniqueIdentTrait;
use App\Common\Entity\Trait\RcdbEntityTrait;
use App\Enum\OperatingStatus;
use App\Repository\CoasterRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CoasterRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Coaster
{
    use NonUniqueIdentTrait;
    use CreateDateTrait;
    use RcdbEntityTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(enumType: OperatingStatus::class, options: ['default' => OperatingStatus::OPERATING_SINCE])]
    private ?OperatingStatus $status = null;

    /**
     * @var Collection<int, Location>
     */
    #[ORM\ManyToMany(targetEntity: Location::class, inversedBy: 'coasters')]
    private Collection $locations;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $images = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $rcdbImageUrl = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $cdnImageUrl = null;

    /**
     * @var Collection<int, Category>
     */
    #[ORM\ManyToMany(targetEntity: Category::class, inversedBy: 'coasters')]
    private Collection $categories;

    #[ORM\OneToOne(inversedBy: 'coaster', cascade: ['persist', 'remove'])]
    private ?Train $train = null;

    #[ORM\OneToOne(inversedBy: 'coaster', cascade: ['persist', 'remove'])]
    private ?Track $track = null;

    #[ORM\ManyToOne(inversedBy: 'coasters')]
    private ?Manufacturer $manufacturer = null;

    #[ORM\Column(options: ['default' => 1200.0])]
    private float $rating = 1200.0;

    #[ORM\Column(options: ['default' => 0])]
    private int $comparisonsCount = 0;

    /**
     * @var Collection<int, Detail>
     */
    #[ORM\ManyToMany(targetEntity: Detail::class, inversedBy: 'coasters')]
    private Collection $details;

    public function __construct()
    {
        $this->locations = new ArrayCollection();
        $this->categories = new ArrayCollection();
        $this->details = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStatus(): ?OperatingStatus
    {
        return $this->status;
    }

    public function setStatus(?OperatingStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    /**
     * @return Collection<int, Location>
     */
    public function getLocations(): Collection
    {
        return $this->locations;
    }

    public function addLocation(Location $location): static
    {
        if (!$this->locations->contains($location)) {
            $this->locations->add($location);
        }

        return $this;
    }

    public function removeLocation(Location $location): static
    {
        $this->locations->removeElement($location);

        return $this;
    }

    public function getImages(): ?array
    {
        return $this->images;
    }

    public function setImages(?array $images): static
    {
        $this->images = $images;

        return $this;
    }

    public function getRcdbImageUrl(): ?string
    {
        return $this->rcdbImageUrl;
    }

    public function setRcdbImageUrl(?string $rcdbImageUrl): static
    {
        $this->rcdbImageUrl = $rcdbImageUrl;

        return $this;
    }

    public function getCdnImageUrl(): ?string
    {
        return $this->cdnImageUrl;
    }

    public function setCdnImageUrl(?string $cdnImageUrl): static
    {
        $this->cdnImageUrl = $cdnImageUrl;

        return $this;
    }

    /**
     * @return Collection<int, Category>
     */
    public function getCategories(): Collection
    {
        return $this->categories;
    }

    public function addCategory(Category $category): static
    {
        if (!$this->categories->contains($category)) {
            $this->categories->add($category);
        }

        return $this;
    }

    public function removeCategory(Category $category): static
    {
        $this->categories->removeElement($category);

        return $this;
    }

    public function getTrain(): ?Train
    {
        return $this->train;
    }

    public function setTrain(?Train $train): static
    {
        $this->train = $train;

        return $this;
    }

    public function getTrack(): ?Track
    {
        return $this->track;
    }

    public function setTrack(?Track $track): static
    {
        $this->track = $track;

        return $this;
    }

    public function getManufacturer(): ?Manufacturer
    {
        return $this->manufacturer;
    }

    public function setManufacturer(?Manufacturer $manufacturer): static
    {
        $this->manufacturer = $manufacturer;

        return $this;
    }

    /**
     * @return Collection<int, Detail>
     */
    public function getDetails(): Collection
    {
        return $this->details;
    }

    public function addDetail(Detail $detail): static
    {
        if (!$this->details->contains($detail)) {
            $this->details->add($detail);
        }

        return $this;
    }

    public function removeDetail(Detail $detail): static
    {
        $this->details->removeElement($detail);

        return $this;
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

    public function getComparisonsCount(): int
    {
        return $this->comparisonsCount;
    }

    public function setComparisonsCount(int $comparisonsCount): static
    {
        $this->comparisonsCount = $comparisonsCount;

        return $this;
    }

    public function getFirstLocationOfType(LocationType $locationType): ?Location
    {
        $locations = array_filter(
            $this->getLocations()->toArray(),
            function (Location $location) use ($locationType) {
                return $location->getType() === $locationType;
            }
        );

        if (count($locations) === 0) {
            return null;
        }

        return $locations[array_key_first($locations)];
    }
}
