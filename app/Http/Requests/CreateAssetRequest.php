<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Business\AssetRegistry\DTOs\CreateAssetData;
use Illuminate\Foundation\Http\FormRequest;
use OpenApi\Attributes as OA;

#[OA\RequestBody(
    request: 'CreateAsset',
    required: true,
    content: new OA\JsonContent(
        required: ['name', 'manufacturer', 'model'],
        properties: [
            new OA\Property(property: 'name', type: 'string', example: 'Server Alpha'),
            new OA\Property(property: 'manufacturer', type: 'string', example: 'Dell'),
            new OA\Property(property: 'model', type: 'string', example: 'PowerEdge R750'),
        ],
    ),
)]
class CreateAssetRequest extends FormRequest
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

    public function toDto(): CreateAssetData
    {
        return new CreateAssetData(
            $this->string('name')->toString(),
            $this->string('manufacturer')->toString(),
            $this->string('model')->toString(),
        );
    }
}
