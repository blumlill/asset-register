<?php declare(strict_types=1);

namespace App\Repository\Eloquent\Mappers;

use App\Business\AssetRegistry\Domain\Entities\ContractAsset;
use App\Repository\Eloquent\Models\ContractAssetModel;

final class ContractAssetMapper
{
    public static function fromModel(ContractAssetModel $model): ContractAsset
    {
        return new ContractAsset(
            (string) $model->id,
            (string) $model->contract_id,
            (string) $model->asset_id,
            $model->serial_number,
        );
    }
}
