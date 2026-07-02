<?php

declare(strict_types=1);

namespace App\Repository\Eloquent\Repositories;

use App\Business\AssetRegistry\Contracts\IContractRepository;
use App\Business\AssetRegistry\Domain\Aggregates\ContractAggregate;
use App\Business\AssetRegistry\Domain\Entities\Contract;
use App\Business\AssetRegistry\Domain\Entities\ContractAsset;
use App\Business\AssetRegistry\Domain\Exceptions\AssetNotAssignedException;
use App\Business\AssetRegistry\Domain\Exceptions\ContractNotFoundException;
use App\Repository\Eloquent\Mappers\AssetMapper;
use App\Repository\Eloquent\Mappers\ContractAssetMapper;
use App\Repository\Eloquent\Mappers\ContractMapper;
use App\Repository\Eloquent\Models\ContractAssetModel;
use App\Repository\Eloquent\Models\ContractModel;
use Carbon\Carbon;

final class EloquentContractRepository implements IContractRepository
{
    public function findById(string $id): Contract
    {
        $model = ContractModel::find($id);

        if ($model === null) {
            throw new ContractNotFoundException($id);
        }

        return ContractMapper::fromModel($model);
    }

    public function findByIdWithAssets(string $id): ContractAggregate
    {
        $model = ContractModel::with(['contractAssets.asset'])->find($id);

        if ($model === null) {
            throw new ContractNotFoundException($id);
        }

        $contract = ContractMapper::fromModel($model);

        $contractAssets = [];
        $assetDetails = [];

        foreach ($model->contractAssets as $caModel) {
            $contractAssets[] = ContractAssetMapper::fromModel($caModel);

            if ($caModel->asset !== null) {
                $assetDetail = AssetMapper::fromModel($caModel->asset);
                $assetDetails[] = $assetDetail;
            }
        }

        return new ContractAggregate($contract, $contractAssets, $assetDetails);
    }

    public function findAll(): array
    {
        return ContractModel::all()
            ->map(ContractMapper::fromModel(...))
            ->all();
    }

    public function saveContract(Contract $contract): Contract
    {
        $model = ContractModel::find($contract->id);

        if ($model === null) {
            $model = new ContractModel;
            $model->id = $contract->id;
        }

        $model->contract_number = $contract->getContractNumber();
        $model->client_name = $contract->getClientName();
        $model->start_date = Carbon::createFromInterface($contract->getStartDate());
        $model->end_date = $contract->getEndDate() !== null
            ? Carbon::createFromInterface($contract->getEndDate())
            : null;

        $model->save();

        return ContractMapper::fromModel($model->fresh() ?? $model);
    }

    public function deleteContract(string $id): void
    {
        if (ContractModel::destroy($id) === 0) {
            throw new ContractNotFoundException($id);
        }
    }

    public function addContractAsset(ContractAsset $contractAsset): ContractAsset
    {
        $model = new ContractAssetModel;
        $model->id = $contractAsset->id;
        $model->contract_id = $contractAsset->contractId;
        $model->asset_id = $contractAsset->assetId;
        $model->serial_number = $contractAsset->serialNumber;
        $model->save();

        return ContractAssetMapper::fromModel($model->fresh() ?? $model);
    }

    public function removeContractAsset(string $contractId, string $assetId): void
    {
        $deleted = ContractAssetModel::where('contract_id', $contractId)
            ->where('asset_id', $assetId)
            ->delete();

        if ($deleted === 0) {
            throw new AssetNotAssignedException($assetId, $contractId);
        }
    }

    public function isSerialNumberTaken(string $serialNumber): bool
    {
        return ContractAssetModel::where('serial_number', $serialNumber)->exists();
    }
}
