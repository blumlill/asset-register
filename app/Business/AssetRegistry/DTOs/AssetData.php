<?php

declare(strict_types=1);

namespace App\Business\AssetRegistry\DTOs;

readonly class AssetData
{
    public function __construct(
        public string $id,
        public string $name,
        public string $manufacturer,
        public string $model,
    ) {}
}
