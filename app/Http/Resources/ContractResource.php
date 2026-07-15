<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Business\AssetRegistry\DTOs\ContractAssetData;
use App\Business\AssetRegistry\DTOs\ContractData;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'ContractResource',
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'contract_number', type: 'string'),
        new OA\Property(property: 'client_name', type: 'string'),
        new OA\Property(property: 'start_date', type: 'string', format: 'date'),
        new OA\Property(property: 'end_date', type: 'string', format: 'date', nullable: true),
        new OA\Property(
            property: 'assets',
            type: 'array',
            nullable: true,
            items: new OA\Items(
                properties: [
                    new OA\Property(property: 'id', type: 'string', format: 'uuid'),
                    new OA\Property(property: 'asset_id', type: 'string', format: 'uuid'),
                    new OA\Property(property: 'serial_number', type: 'string'),
                    new OA\Property(property: 'asset_name', type: 'string'),
                    new OA\Property(property: 'manufacturer', type: 'string'),
                    new OA\Property(property: 'model', type: 'string'),
                ],
            ),
        ),
    ],
)]
class ContractResource extends JsonResource
{
    public function __construct(private readonly ContractData $data)
    {
        parent::__construct($data);
    }

    /** @return array{id: string, contract_number: string, client_name: string, start_date: string, end_date: string|null, assets?: array<int, array<string, string>>} */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->data->id,
            'contract_number' => $this->data->contractNumber,
            'client_name' => $this->data->clientName,
            'start_date' => $this->data->startDate,
            'end_date' => $this->data->endDate,
            ...($this->data->assets !== null
                ? ['assets' => array_map($this->mapAsset(...), $this->data->assets)]
                : []),
        ];
    }

    /** @return array<string, string> */
    private function mapAsset(ContractAssetData $ca): array
    {
        return [
            'id' => $ca->id,
            'asset_id' => $ca->assetId,
            'serial_number' => $ca->serialNumber,
            'asset_name' => $ca->assetName,
            'manufacturer' => $ca->manufacturer,
            'model' => $ca->model,
        ];
    }
}
