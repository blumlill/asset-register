<?php declare(strict_types=1);

namespace App\Repository\Eloquent\Mappers;

use App\Business\AssetRegistry\Domain\Entities\Contract;
use App\Repository\Eloquent\Models\ContractModel;
use DateTimeImmutable;

final class ContractMapper
{
    public static function fromModel(ContractModel $model): Contract
    {
        return new Contract(
            (string) $model->id,
            $model->contract_number,
            $model->client_name,
            new DateTimeImmutable($model->start_date->toDateString()),
            $model->end_date !== null
                ? new DateTimeImmutable($model->end_date->toDateString())
                : null,
        );
    }
}
