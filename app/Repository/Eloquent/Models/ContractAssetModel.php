<?php declare(strict_types=1);

namespace App\Repository\Eloquent\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContractAssetModel extends Model
{
    use HasUuids;

    protected $table = 'contract_assets';

    protected $fillable = [
        'id',
        'contract_id',
        'asset_id',
        'serial_number',
    ];

    public function contract(): BelongsTo
    {
        return $this->belongsTo(ContractModel::class, 'contract_id');
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(AssetModel::class, 'asset_id');
    }
}
