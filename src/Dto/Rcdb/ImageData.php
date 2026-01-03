<?php

declare(strict_types=1);

namespace App\Dto\Rcdb;

class ImageData
{
    public function __construct(
        public readonly array $rcdbJson,
        public readonly ?string $defaultUrl,
    ) {
    }
}
