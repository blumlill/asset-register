<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Business\AssetRegistry\Services\ContractService;
use App\Http\Requests\AssignAssetRequest;
use App\Http\Requests\CreateContractRequest;
use App\Http\Requests\UpdateContractRequest;
use App\Http\Resources\ContractDetailResource;
use App\Http\Resources\ContractResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Contracts', description: 'Contract management')]
class ContractController extends Controller
{
    public function __construct(private readonly ContractService $service) {}

    #[OA\Get(
        path: '/api/v1/contracts',
        summary: 'List all contracts',
        tags: ['Contracts'],
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(
                type: 'array', items: new OA\Items(ref: '#/components/schemas/ContractResource'),
            )),
        ],
    )]
    public function index(): AnonymousResourceCollection
    {
        return ContractResource::collection($this->service->findAll());
    }

    #[OA\Get(
        path: '/api/v1/contracts/{id}',
        summary: 'Get contract detail with embedded assets',
        tags: ['Contracts'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))],
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(ref: '#/components/schemas/ContractDetailResource')),
            new OA\Response(response: 404, description: 'Not found'),
        ],
    )]
    public function show(string $id): ContractDetailResource
    {
        return new ContractDetailResource($this->service->findByIdWithAssets($id));
    }

    #[OA\Post(
        path: '/api/v1/contracts',
        summary: 'Create a contract',
        tags: ['Contracts'],
        requestBody: new OA\RequestBody(ref: '#/components/requestBodies/CreateContract'),
        responses: [
            new OA\Response(response: 201, description: 'Created', content: new OA\JsonContent(ref: '#/components/schemas/ContractResource')),
            new OA\Response(response: 422, description: 'Validation error'),
        ],
    )]
    public function store(CreateContractRequest $request): JsonResponse
    {
        $data = $this->service->create($request->toDto());

        return (new ContractResource($data))->response()->setStatusCode(201);
    }

    #[OA\Put(
        path: '/api/v1/contracts/{id}',
        summary: 'Update a contract',
        tags: ['Contracts'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))],
        requestBody: new OA\RequestBody(ref: '#/components/requestBodies/UpdateContract'),
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(ref: '#/components/schemas/ContractResource')),
            new OA\Response(response: 404, description: 'Not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ],
    )]
    public function update(UpdateContractRequest $request, string $id): ContractResource
    {
        return new ContractResource($this->service->update($id, $request->toDto()));
    }

    #[OA\Delete(
        path: '/api/v1/contracts/{id}',
        summary: 'Delete a contract (cascades contract_assets, preserves assets)',
        tags: ['Contracts'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))],
        responses: [
            new OA\Response(response: 204, description: 'Deleted'),
            new OA\Response(response: 404, description: 'Not found'),
        ],
    )]
    public function destroy(string $id): JsonResponse
    {
        $this->service->delete($id);

        return response()->json(null, 204);
    }

    #[OA\Post(
        path: '/api/v1/contracts/{id}/assets',
        summary: 'Assign an asset to a contract',
        tags: ['Contracts'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))],
        requestBody: new OA\RequestBody(ref: '#/components/requestBodies/AssignAsset'),
        responses: [
            new OA\Response(response: 201, description: 'Assigned', content: new OA\JsonContent(ref: '#/components/schemas/ContractDetailResource')),
            new OA\Response(response: 404, description: 'Not found'),
            new OA\Response(response: 409, description: 'Conflict'),
            new OA\Response(response: 422, description: 'Validation error'),
        ],
    )]
    public function assignAsset(AssignAssetRequest $request, string $id): JsonResponse
    {
        $detail = $this->service->assignAsset($id, $request->toDto());

        return (new ContractDetailResource($detail))->response()->setStatusCode(201);
    }

    #[OA\Delete(
        path: '/api/v1/contracts/{id}/assets/{assetId}',
        summary: 'Remove an asset from a contract',
        tags: ['Contracts'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
            new OA\Parameter(name: 'assetId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 204, description: 'Removed'),
            new OA\Response(response: 404, description: 'Not found'),
        ],
    )]
    public function removeAsset(string $id, string $assetId): JsonResponse
    {
        $this->service->removeAsset($id, $assetId);

        return response()->json(null, 204);
    }
}
