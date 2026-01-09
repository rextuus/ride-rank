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
use App\Entity\CoasterMetadata;
use App\Entity\Detail;
use App\Entity\Model;
use App\Entity\Location;
use App\Entity\Manufacturer;
use App\Entity\Track;
use App\Entity\TrackElement;
use App\Entity\Train;
use App\Service\Rcdb\Exception\IsNeitherCoasterNorParcEntryException;
use App\Enum\DetailType;
use App\Enum\OperatingStatus;
use Doctrine\ORM\EntityManagerInterface;
use Exception;

readonly class ImportService
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    /**
     * @param array<string, mixed> $data
     * @throws IsNeitherCoasterNorParcEntryException
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

    /**
     * @throws IsNeitherCoasterNorParcEntryException
     */
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

        $models = array_map(
            fn(array $m) => new CategoryData($m['ident'], $m['id'], $m['url']),
            $data['model'] ?? []
        );

        // Extract opening year (first date from statusDate array)
        $openingYear = null;
        if (!empty($data['statusDate']) && is_array($data['statusDate'])) {
            $firstYear = reset($data['statusDate']);
            $openingYear = is_numeric($firstYear) ? (int) $firstYear : null;
        }

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

        if (!isset($data['status'])) {
            throw new IsNeitherCoasterNorParcEntryException('Missing status');
        }

        return new RollerCoasterData(
            $rcdbId,
            $data['name'],
            $locations,
            $data['status'],
            $data['statusDate'],
            $openingYear,
            $categories,
            $data['manufacturer'] ?? null,
            $models,
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
        $coaster = $this->entityManager->getRepository(Coaster::class)->findOneBy(['rcdbId' => $dto->rcdbId]);
        if (!$coaster) {
            $coaster = new Coaster();
            $coaster->setRcdbId($dto->rcdbId);
        }

        $coaster->setName($dto->name);
        $coaster->setIdent($dto->name);
        $coaster->setOpeningYear($dto->openingYear);

        $status = OperatingStatus::tryFrom($dto->status);
        if ($status === null) {
            if (str_contains($dto->status, 'http') || $dto->status === '' || str_contains($dto->status, 'Telephone')){
                throw new IsNeitherCoasterNorParcEntryException('Unknown operating status: ' . $dto->status);
            }

            throw new Exception('Unknown operating status: ' . $dto->status);
        }

        $coaster->setStatus($status);
        $coaster->setRcdbImageUrl($dto->images->defaultUrl);

        // Create or update metadata
        $metadata = $coaster->getMetadata();
        if (!$metadata) {
            $metadata = new CoasterMetadata($coaster);
            $coaster->setMetadata($metadata);
        }
        $metadata->setImages($dto->images->rcdbJson);
        $metadata->setStatusDates($dto->statusDate ?: null);

        $manufacturerName = $dto->manufacturer ?: 'Unknown Manufacturer';
        $manufacturer = $this->entityManager->getRepository(Manufacturer::class)->findOneBy(
            ['ident' => $manufacturerName]
        );
        if (!$manufacturer) {
            $manufacturer = $this->entityManager->getRepository(Manufacturer::class)->findOneBy(
                ['name' => $manufacturerName]
            );
        }

        if (!$manufacturer) {
            $manufacturer = new Manufacturer();
            $manufacturer->setName($manufacturerName);
            $manufacturer->setIdent($manufacturerName);
            if (!$dryRun) {
                $this->entityManager->persist($manufacturer);
                // Flush to avoid unique constraint violation if used again in same transaction
                $this->entityManager->flush();
            }
        }
        $coaster->setManufacturer($manufacturer);

        // Handle model information
        foreach ($dto->model as $modelData) {
            $model = null;
            if ($modelData->id) {
                $model = $this->entityManager->getRepository(Model::class)->findOneBy(['rcdbId' => $modelData->id]);
            }

            if (!$model) {
                $model = $this->entityManager->getRepository(Model::class)->findOneBy(['ident' => $modelData->ident]);
            }

            if (!$model) {
                $model = $this->entityManager->getRepository(Model::class)->findOneBy(['name' => $modelData->ident]);
            }

            if (!$model) {
                $model = new Model();
                $model->setName($modelData->ident);
                $model->setIdent($modelData->ident);
                if ($modelData->id) {
                    $model->setRcdbId($modelData->id);
                }
                $model->setRcdbUrl($modelData->url);

                if (!$dryRun) {
                    $this->entityManager->persist($model);
                    $this->entityManager->flush();
                }
            }

            if (!$coaster->getModels()->contains($model)) {
                $coaster->addModel($model);
            }
        }

        foreach ($dto->location as $index => $locData) {
            $location = $this->entityManager->getRepository(Location::class)->findOneBy(['rcdbId' => $locData->id]);
            if (!$location) {
                $location = $this->entityManager->getRepository(Location::class)->findOneBy(['ident' => $locData->ident]);
            }

            if (!$location) {
                $location = new Location();
                $location->setName($locData->ident);
                $location->setIdent($locData->ident);
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
                    $this->entityManager->flush();
                }
            }
            if (!$coaster->getLocations()->contains($location)) {
                $coaster->addLocation($location);
            }
        }

        foreach ($dto->categories as $catData) {
            $category = null;
            if ($catData->id) {
                $category = $this->entityManager->getRepository(Category::class)->findOneBy(['rcdbId' => $catData->id]);
            }

            if (!$category) {
                $category = $this->entityManager->getRepository(Category::class)->findOneBy(['ident' => $catData->ident]);
            }

            if (!$category) {
                $category = $this->entityManager->getRepository(Category::class)->findOneBy(['name' => $catData->ident]);
            }

            if (!$category) {
                $category = new Category();
                $category->setName($catData->ident);
                $category->setIdent($catData->ident);
                if ($catData->id) {
                    $category->setRcdbId($catData->id);
                }
                $category->setRcdbUrl($catData->url);
                if (!$dryRun) {
                    $this->entityManager->persist($category);
                    $this->entityManager->flush();
                }
            }
            if (!$coaster->getCategories()->contains($category)) {
                $coaster->addCategory($category);
            }
        }

        if ($dto->track) {
            $track = $coaster->getTrack() ?: new Track();
            $track->setLength($dto->track->length);
            $track->setHeight($dto->track->height);
            $track->setDrop($dto->track->drop);
            $track->setSpeed($dto->track->speed);
            $track->setInversions($dto->track->inversions);
            $track->setDuration($dto->track->duration);
            $track->setVerticalAngle($dto->track->verticalAngle);

            // For simplicity, we clear elements and re-add them to handle position changes easily
            // In a real scenario, we might want to update existing ones.
            foreach ($track->getElements() as $element) {
                $track->removeElement($element);
            }

            $addedElements = [];
            foreach ($dto->track->elements as $index => $elData) {
                $ident = trim(strtolower($elData->ident));
                $element = null;

                if (array_key_exists($ident, $addedElements)) {
                    $element = $addedElements[$ident];
                }

                if (!$element) {
                    $element = $this->entityManager->getRepository(TrackElement::class)->findOneBy(['rcdbId' => $elData->id]);
                }

                if (!$element) {
                    $element = $this->entityManager->getRepository(TrackElement::class)->findOneBy(['ident' => $ident]);
                }

                if (!$element) {
                    $element = new TrackElement();
                    $element->setName($elData->ident);
                    $element->setIdent($ident);
                    if ($elData->id) {
                        $element->setRcdbId($elData->id);
                    }
                    $element->setRcdbUrl($elData->url);
                    if (!$dryRun) {
                        $this->entityManager->persist($element);
                        $this->entityManager->flush();
                    }
                }

                $addedElements[$ident] = $element;

                $track->addElement($element);
            }
            $coaster->setTrack($track);
        }

        if ($dto->train) {
            $train = $coaster->getTrain() ?: new Train();
            $train->setArrangement($dto->train->arrangement);

            if (!empty($dto->train->restraints)) {
                $resData = $dto->train->restraints[0];
                $restraint = $this->entityManager->getRepository(Location::class)->findOneBy(['rcdbId' => $resData->id]);
                if (!$restraint) {
                    $restraint = $this->entityManager->getRepository(Location::class)->findOneBy(['ident' => $resData->ident]);
                }

                if (!$restraint) {
                    $restraint = new Location();
                    $restraint->setName($resData->ident);
                    $restraint->setIdent($resData->ident);
                    $restraint->setRcdbId($resData->id);
                    $restraint->setRcdbUrl($resData->url);
                    $restraint->setType(LocationType::NOT_DETERMINED);
                    if (!$dryRun) {
                        $this->entityManager->persist($restraint);
                        $this->entityManager->flush();
                    }
                }
                $train->setRestraint($restraint);
            }

            if (!empty($dto->train->builtBy)) {
                $bbData = $dto->train->builtBy[0];
                $builder = null;
                if ($bbData->id) {
                    $builder = $this->entityManager->getRepository(Manufacturer::class)->findOneBy(
                        ['rcdbId' => $bbData->id]
                    );
                }

                if (!$builder) {
                    $builder = $this->entityManager->getRepository(Manufacturer::class)->findOneBy(
                        ['ident' => $bbData->ident]
                    );
                }

                if (!$builder) {
                    $builder = new Manufacturer();
                    $builder->setName($bbData->ident);
                    $builder->setIdent($bbData->ident);
                    if ($bbData->id) {
                        $builder->setRcdbId($bbData->id);
                    }
                    $builder->setRcdbUrl($bbData->url);
                    if (!$dryRun) {
                        $this->entityManager->persist($builder);
                        $this->entityManager->flush();
                    }
                }
                $train->setBuiltBy($builder);
            }
            $coaster->setTrain($train);
        }

        // Clear existing details to avoid duplicates on re-import
        foreach ($coaster->getDetails() as $detail) {
            $coaster->removeDetail($detail);
        }

        foreach ($dto->details as $content) {
            $detail = $this->entityManager->getRepository(Detail::class)->findOneBy(['ident' => $content]);
            if (!$detail) {
                $detail = new Detail();
                $detail->setContent($content);
                $detail->setIdent($content);
                $detail->setName($content);
                $detail->setType(DetailType::DETAIL);
                if (!$dryRun) {
                    $this->entityManager->persist($detail);
                    $this->entityManager->flush();
                }
            }
            if (!$coaster->getDetails()->contains($detail)) {
                $coaster->addDetail($detail);
            }
        }

        foreach ($dto->facts as $content) {
            $detail = $this->entityManager->getRepository(Detail::class)->findOneBy(['ident' => $content]);
            if (!$detail) {
                $detail = new Detail();
                $detail->setContent($content);
                $detail->setIdent($content);
                $detail->setName($content);
                $detail->setType(DetailType::FACT);
                if (!$dryRun) {
                    $this->entityManager->persist($detail);
                    $this->entityManager->flush();
                }
            }
            if (!$coaster->getDetails()->contains($detail)) {
                $coaster->addDetail($detail);
            }
        }

        foreach ($dto->history as $content) {
            $detail = $this->entityManager->getRepository(Detail::class)->findOneBy(['ident' => $content]);
            if (!$detail) {
                $detail = new Detail();
                $detail->setContent($content);
                $detail->setIdent($content);
                $detail->setName($content);
                $detail->setType(DetailType::HISTORY);
                if (!$dryRun) {
                    $this->entityManager->persist($detail);
                    $this->entityManager->flush();
                }
            }
            if (!$coaster->getDetails()->contains($detail)) {
                $coaster->addDetail($detail);
            }
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
