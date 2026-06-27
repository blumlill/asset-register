<?php declare(strict_types=1);

namespace App\Repository\Eloquent\Mappers;

use App\Business\AssetRegistry\Domain\Entities\Asset;
use App\Repository\Eloquent\Models\AssetModel;
use DateTimeImmutable;

final class AssetMapper
{
    public static function fromModel(AssetModel $model): Asset
    {
        return new Asset(
            (string) $model->id,
            $model->name,
            $model->manufacturer,
            $model->model,
            $model->deleted_at !== null
                ? new DateTimeImmutable($model->deleted_at->toDateTimeString())
                : null,
        );
    }
}
