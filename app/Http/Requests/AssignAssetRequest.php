<?php declare(strict_types=1);

namespace App\Http\Requests;

use App\Business\AssetRegistry\DTOs\AssignAssetData;
use Illuminate\Foundation\Http\FormRequest;
use OpenApi\Attributes as OA;

#[OA\RequestBody(
    request: 'AssignAsset',
    required: true,
    content: new OA\JsonContent(
        required: ['asset_id', 'serial_number'],
        properties: [
            new OA\Property(property: 'asset_id', type: 'string', format: 'uuid', example: '550e8400-e29b-41d4-a716-446655440000'),
            new OA\Property(property: 'serial_number', type: 'string', example: 'SN-2026-001'),
        ],
    ),
)]
class AssignAssetRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'asset_id' => ['required', 'string', 'uuid', 'exists:assets,id'],
            'serial_number' => ['required', 'string', 'max:100'],
        ];
    }

    public function toDto(): AssignAssetData
    {
        return new AssignAssetData(
            $this->string('asset_id')->toString(),
            $this->string('serial_number')->toString(),
        );
    }
}
