<?php

declare(strict_types=1);

namespace App\Service\Rcdb;

use App\Common\Entity\Enum\LocationType;
use App\Dto\Rcdb\CategoryData;
use App\Dto\Rcdb\ImageData;
use App\Dto\Rcdb\LocationData;
use App\Dto\Rcdb\RollerCoasterData;
use App\Dto\Rcdb\TrackData;
use App\Dto\Rcdb\TrackElementData;
use App\Dto\Rcdb\TrainData;
use App\Entity\Category;
use App\Entity\Coaster;
use App\Entity\Detail;
use App\Entity\Location;
use App\Entity\Manufacturer;
use App\Entity\Track;
use App\Entity\TrackElement;
use App\Entity\Train;
use App\Enum\DetailType;
use Doctrine\ORM\EntityManagerInterface;

class ImportService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public function importFromCrawlerArray(int $rcdbId, array $data, bool $dryRun = false): Coaster
    {
        $dto = $this->mapArrayToDto($rcdbId, $data);

        if ($dryRun) {
            return $this->importCoaster($dto, true);
        }

        return $this->entityManager->wrapInTransaction(function () use ($dto) {
            return $this->importCoaster($dto);
        });
    }

    private function mapArrayToDto(int $rcdbId, array $data): RollerCoasterData
    {
        $locations = array_map(
            fn(array $loc) => new LocationData($loc['ident'], $loc['id'], $loc['url']),
            $data['location'] ?? []
        );

        $categories = array_map(
            fn(array $cat) => new CategoryData($cat['ident'], $cat['id'], $cat['url']),
            $data['categories'] ?? []
        );

        $track = null;
        if (isset($data['tracks'])) {
            $t = $data['tracks'];
            $elements = array_map(
                fn(array $el) => new TrackElementData($el['ident'], $el['id'], $el['url']),
                $t['Elements'] ?? []
            );

            $track = new TrackData(
                $this->parseFloat($t['Length'] ?? null),
                $this->parseFloat($t['Height'] ?? null),
                $this->parseFloat($t['Drop'] ?? null),
                $this->parseFloat($t['Speed'] ?? null),
                isset($t['Inversions']) ? (int) $t['Inversions'] : null,
                $this->parseDuration($t['Duration'] ?? null),
                isset($t['Vertical Angle']) ? (int) $t['Vertical Angle'] : null,
                $elements
            );
        }

        $train = null;
        if (isset($data['trains'])) {
            $tr = $data['trains'];
            $restraints = array_map(
                fn(array $res) => new LocationData($res['ident'], $res['id'], $res['url']),
                $tr['Restraints'] ?? []
            );
            $builtBy = array_map(
                fn(array $bb) => new TrackElementData($bb['ident'], $bb['id'], $bb['url']),
                $tr['Built by'] ?? []
            );

            $train = new TrainData(
                $tr['Arrangement'] ?? null,
                $restraints,
                $builtBy
            );
        }

        $images = new ImageData(
            $data['images']['rcdb_json'] ?? [],
            $data['images']['default'] ?? null
        );

        return new RollerCoasterData(
            $rcdbId,
            $data['name'],
            $locations,
            $data['status'],
            $categories,
            $data['manufacturer'] ?? null,
            $track,
            $train,
            $images,
            $data['details'] ?? [],
            $data['facts'] ?? [],
            $data['history'] ?? []
        );
    }

    private function importCoaster(RollerCoasterData $dto, bool $dryRun = false): Coaster
    {
        $coaster = new Coaster();
        $coaster->setName($dto->name);
        $coaster->setIdent($dto->name); // Assuming ident is name for now
        $coaster->setStatus($dto->status);
        $coaster->setImages($dto->images->rcdbJson);
        $coaster->setRcdbImageUrl($dto->images->defaultUrl);
        $coaster->setRcdbId($dto->rcdbId);

        if ($dto->manufacturer) {
            $manufacturer = $this->entityManager->getRepository(Manufacturer::class)->findOneBy(
                ['name' => $dto->manufacturer]
            );
            if (!$manufacturer) {
                $manufacturer = new Manufacturer();
                $manufacturer->setName($dto->manufacturer);
                if (!$dryRun) {
                    $this->entityManager->persist($manufacturer);
                }
            }
            $coaster->setManufacturer($manufacturer);
        } else {
            $manufacturer = $this->entityManager->getRepository(Manufacturer::class)->findOneBy(
                ['name' => 'Unknown Manufacturer']
            );
            if (!$manufacturer) {
                $manufacturer = new Manufacturer();
                $manufacturer->setName('Unknown Manufacturer');
                if (!$dryRun) {
                    $this->entityManager->persist($manufacturer);
                }
            }
            $coaster->setManufacturer($manufacturer);
        }

        foreach ($dto->location as $index => $locData) {
            $location = $this->entityManager->getRepository(Location::class)->findOneBy(['rcdbId' => $locData->id]);
            if (!$location) {
                $location = new Location();
                $location->setName($locData->ident);
                $location->setRcdbId($locData->id);
                $location->setRcdbUrl($locData->url);

                // Set location type based on hierarchy index
                $type = match ($index) {
                    0 => LocationType::AMUSEMENT_PARK,
                    1 => LocationType::CITY,
                    2 => LocationType::STATE,
                    3 => LocationType::COUNTRY,
                    default => LocationType::NOT_DETERMINED,
                };
                $location->setType($type);

                if (!$dryRun) {
                    $this->entityManager->persist($location);
                }
            }
            $coaster->addLocation($location);
        }

        foreach ($dto->categories as $catData) {
            $category = null;
            if ($catData->id) {
                $category = $this->entityManager->getRepository(Category::class)->findOneBy(['rcdbId' => $catData->id]);
            }
            if (!$category) {
                $category = new Category();
                $category->setName($catData->ident);
                if ($catData->id) {
                    $category->setRcdbId($catData->id);
                }
                $category->setRcdbUrl($catData->url);
                if (!$dryRun) {
                    $this->entityManager->persist($category);
                }
            }
            $coaster->addCategory($category);
        }

        if ($dto->track) {
            $track = new Track();
            $track->setLength($dto->track->length);
            $track->setHeight($dto->track->height);
            $track->setDrop($dto->track->drop);
            $track->setSpeed($dto->track->speed);
            $track->setInversions($dto->track->inversions);
            $track->setDuration($dto->track->duration);
            $track->setVerticalAngle($dto->track->verticalAngle);

            foreach ($dto->track->elements as $index => $elData) {
                $element = new TrackElement();
                $element->setName($elData->ident);
                $element->setIdent($elData->ident);
                if ($elData->id) {
                    $element->setRcdbId($elData->id);
                }
                $element->setRcdbUrl($elData->url);
                $element->setPosition($index + 1);
                $track->addElement($element);
            }
            $coaster->setTrack($track);
        }

        if ($dto->train) {
            $train = new Train();
            $train->setArrangement($dto->train->arrangement);

            if (!empty($dto->train->restraints)) {
                $resData = $dto->train->restraints[0];
                $restraint = $this->entityManager->getRepository(Location::class)->findOneBy(['rcdbId' => $resData->id]
                );
                if (!$restraint) {
                    $restraint = new Location();
                    $restraint->setName($resData->ident);
                    $restraint->setRcdbId($resData->id);
                    $restraint->setRcdbUrl($resData->url);
                    $restraint->setType(LocationType::NOT_DETERMINED);
                    if (!$dryRun) {
                        $this->entityManager->persist($restraint);
                    }
                }
                $train->setRestraint($restraint);
            }

            if (!empty($dto->train->builtBy)) {
                $bbData = $dto->train->builtBy[0];
                $builder = $this->entityManager->getRepository(Manufacturer::class)->findOneBy(
                    ['name' => $bbData->ident]
                );
                if (!$builder) {
                    $builder = new Manufacturer();
                    $builder->setName($bbData->ident);
                    if ($bbData->id) {
                        $builder->setRcdbId($bbData->id);
                    }
                    $builder->setRcdbUrl($bbData->url);
                    if (!$dryRun) {
                        $this->entityManager->persist($builder);
                    }
                }
                $train->setBuiltBy($builder);
            }
            $coaster->setTrain($train);
        }

        foreach ($dto->details as $content) {
            $detail = new Detail();
            $detail->setContent($content);
            $detail->setType(DetailType::DETAIL);
            $coaster->addDetail($detail);
        }

        foreach ($dto->facts as $content) {
            $detail = new Detail();
            $detail->setContent($content);
            $detail->setType(DetailType::FACT);
            $coaster->addDetail($detail);
        }

        foreach ($dto->history as $content) {
            $detail = new Detail();
            $detail->setContent($content);
            $detail->setType(DetailType::HISTORY);
            $coaster->addDetail($detail);
        }

        if (!$dryRun) {
            $this->entityManager->persist($coaster);
        }

        return $coaster;
    }

    private function parseFloat(?string $value): ?float
    {
        if ($value === null) {
            return null;
        }

        preg_match('/[0-9.]+/', $value, $matches);
        return isset($matches[0]) ? (float) $matches[0] : null;
    }

    private function parseDuration(?string $value): ?int
    {
        if ($value === null) {
            return null;
        }

        if (preg_match('/(\d+):(\d+)/', $value, $matches)) {
            return (int) $matches[1] * 60 + (int) $matches[2];
        }

        return null;
    }
}
