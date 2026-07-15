<?php

declare(strict_types=1);

namespace App\Business\AssetRegistry\Domain\Aggregates;

use App\Business\AssetRegistry\Domain\Entities\Asset;
use App\Business\AssetRegistry\Domain\Entities\Contract;
use App\Business\AssetRegistry\Domain\Entities\ContractAsset;
use App\Business\AssetRegistry\Domain\Exceptions\AssetAlreadyAssignedException;

final class ContractAggregate
{
    /** @var array<string, ContractAsset> keyed by assetId */
    private array $contractAssets = [];

    /** @var array<string, Asset> keyed by assetId — populated for detail reads */
    private array $assetDetails = [];

    /**
     * @param  ContractAsset[]  $contractAssets
     * @param  Asset[]  $assetDetails
     */
    public function __construct(
        public readonly Contract $contract,
        array $contractAssets = [],
        array $assetDetails = [],
    ) {
        foreach ($contractAssets as $ca) {
            $this->contractAssets[$ca->assetId] = $ca;
        }

        foreach ($assetDetails as $asset) {
            $this->assetDetails[$asset->id] = $asset;
        }
    }

    /** @return ContractAsset[] */
    public function getContractAssets(): array
    {
        return array_values($this->contractAssets);
    }

    public function getAssetDetail(string $assetId): ?Asset
    {
        return $this->assetDetails[$assetId] ?? null;
    }

    public function assignAsset(ContractAsset $contractAsset): void
    {
        if (isset($this->contractAssets[$contractAsset->assetId])) {
            throw new AssetAlreadyAssignedException(
                $contractAsset->assetId,
                $this->contract->id,
            );
        }

        $this->contractAssets[$contractAsset->assetId] = $contractAsset;
    }

    public function addAssetDetail(Asset $asset): void
    {
        $this->assetDetails[$asset->id] = $asset;
    }
}
