<?php

declare(strict_types=1);

namespace App\Business\AssetRegistry\DTOs;

readonly class ContractInputData
{
    public function __construct(
        public string $contractNumber,
        public string $clientName,
        public string $startDate,
        public ?string $endDate,
    ) {}
}
