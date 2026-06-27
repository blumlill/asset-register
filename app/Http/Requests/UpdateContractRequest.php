<?php declare(strict_types=1);

namespace App\Http\Requests;

use App\Business\AssetRegistry\DTOs\UpdateContractData;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use OpenApi\Attributes as OA;

#[OA\RequestBody(
    request: 'UpdateContract',
    required: true,
    content: new OA\JsonContent(
        required: ['contract_number', 'client_name', 'start_date'],
        properties: [
            new OA\Property(property: 'contract_number', type: 'string', example: 'C-2026-001'),
            new OA\Property(property: 'client_name', type: 'string', example: 'Acme Corp'),
            new OA\Property(property: 'start_date', type: 'string', format: 'date', example: '2026-01-01'),
            new OA\Property(property: 'end_date', type: 'string', format: 'date', example: '2026-12-31', nullable: true),
        ],
    ),
)]
class UpdateContractRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'contract_number' => [
                'required',
                'string',
                'max:100',
                Rule::unique('contracts', 'contract_number')->ignore($this->route('id')),
            ],
            'client_name' => ['required', 'string', 'max:255'],
            'start_date' => ['required', 'date_format:Y-m-d'],
            'end_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:start_date'],
        ];
    }

    public function toDto(): UpdateContractData
    {
        return new UpdateContractData(
            $this->string('contract_number')->toString(),
            $this->string('client_name')->toString(),
            $this->string('start_date')->toString(),
            $this->input('end_date'),
        );
    }
}
