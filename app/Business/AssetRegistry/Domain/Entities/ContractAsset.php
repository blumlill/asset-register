<?php

declare(strict_types=1);

namespace App\Business\AssetRegistry\Domain\Entities;

readonly class ContractAsset
{
    public function __construct(
        public string $id,
        public string $contractId,
        public string $assetId,
        public string $serialNumber,
    ) {}
}
