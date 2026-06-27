<?php declare(strict_types=1);

namespace App\Repository\Eloquent\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ContractModel extends Model
{
    use HasUuids;

    protected $table = 'contracts';

    protected $fillable = [
        'id',
        'contract_number',
        'client_name',
        'start_date',
        'end_date',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function contractAssets(): HasMany
    {
        return $this->hasMany(ContractAssetModel::class, 'contract_id');
    }
}
