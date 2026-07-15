<?php

declare(strict_types=1);

namespace App\Business\AssetRegistry\DTOs;

readonly class ContractData
{
    /**
     * @param  list<ContractAssetData>|null  $assets
     */
    public function __construct(
        public string $id,
        public string $contractNumber,
        public string $clientName,
        public string $startDate,
        public ?string $endDate,
        public ?array $assets = null,
    ) {}
}
