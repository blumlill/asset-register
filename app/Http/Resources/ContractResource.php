<?php declare(strict_types=1);

namespace App\Http\Resources;

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
    ],
)]
class ContractResource extends JsonResource
{
    public function __construct(private readonly ContractData $data)
    {
        parent::__construct($data);
    }

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->data->id,
            'contract_number' => $this->data->contractNumber,
            'client_name' => $this->data->clientName,
            'start_date' => $this->data->startDate,
            'end_date' => $this->data->endDate,
        ];
    }
}
