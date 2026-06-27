<?php declare(strict_types=1);

namespace App\Http\Resources;

use App\Business\AssetRegistry\DTOs\AssetData;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'AssetResource',
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'name', type: 'string'),
        new OA\Property(property: 'manufacturer', type: 'string'),
        new OA\Property(property: 'model', type: 'string'),
    ],
)]
class AssetResource extends JsonResource
{
    public function __construct(private readonly AssetData $data)
    {
        parent::__construct($data);
    }

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->data->id,
            'name' => $this->data->name,
            'manufacturer' => $this->data->manufacturer,
            'model' => $this->data->model,
        ];
    }
}
