<?php declare(strict_types=1);

namespace App\Business\AssetRegistry\Events;

final class AssetAssigned
{
    public function __construct(
        public readonly string $contractId,
        public readonly string $assetId,
        public readonly string $serialNumber,
    ) {}
}
