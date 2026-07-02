<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Business\AssetRegistry\DTOs\UpdateAssetData;
use Illuminate\Foundation\Http\FormRequest;
use OpenApi\Attributes as OA;

#[OA\RequestBody(
    request: 'UpdateAsset',
    required: true,
    content: new OA\JsonContent(
        required: ['name', 'manufacturer', 'model'],
        properties: [
            new OA\Property(property: 'name', type: 'string', example: 'Server Alpha v2'),
            new OA\Property(property: 'manufacturer', type: 'string', example: 'Dell'),
            new OA\Property(property: 'model', type: 'string', example: 'PowerEdge R750'),
        ],
    ),
)]
class UpdateAssetRequest extends FormRequest
{
    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'manufacturer' => ['required', 'string', 'max:255'],
            'model' => ['required', 'string', 'max:255'],
        ];
    }

    public function toDto(): UpdateAssetData
    {
        return new UpdateAssetData(
            $this->string('name')->toString(),
            $this->string('manufacturer')->toString(),
            $this->string('model')->toString(),
        );
    }
}
