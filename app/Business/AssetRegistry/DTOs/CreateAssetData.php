<?php

declare(strict_types=1);

namespace App\Business\AssetRegistry\DTOs;

readonly class CreateAssetData
{
    public function __construct(
        public string $name,
        public string $manufacturer,
        public string $model,
    ) {}
}
