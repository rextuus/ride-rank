<?php

declare(strict_types=1);

namespace App\Dto\Rcdb;

class RollerCoasterData
{
    /**
     * @param LocationData[] $location
     * @param CategoryData[] $categories
     * @param CategoryData[] $model
     * @param string[] $details
     * @param string[] $facts
     * @param string[] $history
     */
    public function __construct(
        public readonly int $rcdbId,
        public readonly string $name,
        public readonly array $location,
        public readonly string $status,
        public readonly array $statusDate,
        public readonly ?int $openingYear,
        public readonly array $categories,
        public readonly ?string $manufacturer,
        public readonly array $model,
        public readonly ?TrackData $track,
        public readonly ?TrainData $train,
        public readonly ImageData $images,
        public readonly array $details = [],
        public readonly array $facts = [],
        public readonly array $history = [],
    ) {
    }
}
