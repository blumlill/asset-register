<?php declare(strict_types=1);

namespace App\Business\AssetRegistry\DTOs;

readonly class ContractAssetData
{
    public function __construct(
        public string $id,
        public string $assetId,
        public string $serialNumber,
        public string $assetName,
        public string $manufacturer,
        public string $model,
    ) {}
}
