<?php declare(strict_types=1);

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
     * @param ContractAsset[] $contractAssets
     * @param Asset[]         $assetDetails
     */
    public function __construct(
        private readonly Contract $contract,
        array $contractAssets = [],
        array $assetDetails = [],
    ) {
        foreach ($contractAssets as $ca) {
            $this->contractAssets[$ca->getAssetId()] = $ca;
        }

        foreach ($assetDetails as $asset) {
            $this->assetDetails[$asset->getId()] = $asset;
        }
    }

    public function getContract(): Contract
    {
        return $this->contract;
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
        if (isset($this->contractAssets[$contractAsset->getAssetId()])) {
            throw new AssetAlreadyAssignedException(
                $contractAsset->getAssetId(),
                $this->contract->getId(),
            );
        }

        $this->contractAssets[$contractAsset->getAssetId()] = $contractAsset;
    }

    public function removeAsset(string $assetId): void
    {
        unset($this->contractAssets[$assetId]);
        unset($this->assetDetails[$assetId]);
    }
}
