<?php declare(strict_types=1);

namespace App\Business\AssetRegistry\Domain\Entities;

final class ContractAsset
{
    public function __construct(
        private readonly string $id,
        private readonly string $contractId,
        private readonly string $assetId,
        private readonly string $serialNumber,
    ) {}

    public function getId(): string
    {
        return $this->id;
    }

    public function getContractId(): string
    {
        return $this->contractId;
    }

    public function getAssetId(): string
    {
        return $this->assetId;
    }

    public function getSerialNumber(): string
    {
        return $this->serialNumber;
    }
}
