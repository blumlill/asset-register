<?php

declare(strict_types=1);

namespace App\Repository\Eloquent\Repositories;

use App\Business\AssetRegistry\Contracts\IAssetRepository;
use App\Business\AssetRegistry\Domain\Entities\Asset;
use App\Business\AssetRegistry\Domain\Exceptions\AssetNotFoundException;
use App\Repository\Eloquent\Mappers\AssetMapper;
use App\Repository\Eloquent\Models\AssetModel;
use App\Repository\Eloquent\Models\ContractAssetModel;

final class EloquentAssetRepository implements IAssetRepository
{
    public function findById(string $id): Asset
    {
        $model = AssetModel::find($id);

        if ($model === null) {
            throw new AssetNotFoundException($id);
        }

        return AssetMapper::fromModel($model);
    }

    public function findAll(): array
    {
        return AssetModel::all()
            ->map(AssetMapper::fromModel(...))
            ->all();
    }

    public function save(Asset $asset): Asset
    {
        $model = AssetModel::withTrashed()->find($asset->id);

        if ($model === null) {
            $model = new AssetModel;
            $model->id = $asset->id;
        }

        $model->name = $asset->getName();
        $model->manufacturer = $asset->getManufacturer();
        $model->model = $asset->getModel();
        $model->deleted_at = $asset->getDeletedAt()?->format('Y-m-d H:i:s');

        $model->save();

        return AssetMapper::fromModel($model->fresh() ?? $model);
    }

    public function hasActiveAssignments(string $assetId): bool
    {
        return ContractAssetModel::where('asset_id', $assetId)->exists();
    }
}
