<?php

declare(strict_types=1);

namespace App\Repository\Eloquent\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class AssetModel extends Model
{
    use HasUuids;
    use SoftDeletes;

    protected $table = 'assets';

    protected $fillable = [
        'id',
        'name',
        'manufacturer',
        'model',
    ];

    /** @return HasMany<ContractAssetModel, $this> */
    public function contractAssets(): HasMany
    {
        return $this->hasMany(ContractAssetModel::class, 'asset_id');
    }
}
