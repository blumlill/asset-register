<?php declare(strict_types=1);

namespace App\Http\Controllers;

use App\Business\AssetRegistry\Services\AssetService;
use App\Http\Requests\CreateAssetRequest;
use App\Http\Requests\UpdateAssetRequest;
use App\Http\Resources\AssetResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Assets', description: 'Asset management')]
class AssetController extends Controller
{
    public function __construct(private readonly AssetService $service) {}

    #[OA\Get(
        path: '/api/v1/assets',
        summary: 'List all assets',
        tags: ['Assets'],
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(
                type: 'array', items: new OA\Items(ref: '#/components/schemas/AssetResource'),
            )),
        ],
    )]
    public function index(): AnonymousResourceCollection
    {
        $assets = $this->service->findAll();

        return AssetResource::collection(
            collect($assets)->map(fn ($d) => new AssetResource($d)),
        );
    }

    #[OA\Get(
        path: '/api/v1/assets/{id}',
        summary: 'Get a single asset',
        tags: ['Assets'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))],
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(ref: '#/components/schemas/AssetResource')),
            new OA\Response(response: 404, description: 'Not found'),
        ],
    )]
    public function show(string $id): AssetResource
    {
        return new AssetResource($this->service->findById($id));
    }

    #[OA\Post(
        path: '/api/v1/assets',
        summary: 'Create an asset',
        tags: ['Assets'],
        requestBody: new OA\RequestBody(ref: '#/components/requestBodies/CreateAsset'),
        responses: [
            new OA\Response(response: 201, description: 'Created', content: new OA\JsonContent(ref: '#/components/schemas/AssetResource')),
            new OA\Response(response: 422, description: 'Validation error'),
        ],
    )]
    public function store(CreateAssetRequest $request): JsonResponse
    {
        $data = $this->service->create($request->toDto());

        return (new AssetResource($data))->response()->setStatusCode(201);
    }

    #[OA\Put(
        path: '/api/v1/assets/{id}',
        summary: 'Update an asset',
        tags: ['Assets'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))],
        requestBody: new OA\RequestBody(ref: '#/components/requestBodies/UpdateAsset'),
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(ref: '#/components/schemas/AssetResource')),
            new OA\Response(response: 404, description: 'Not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ],
    )]
    public function update(UpdateAssetRequest $request, string $id): AssetResource
    {
        return new AssetResource($this->service->update($id, $request->toDto()));
    }

    #[OA\Delete(
        path: '/api/v1/assets/{id}',
        summary: 'Soft-delete an asset',
        tags: ['Assets'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))],
        responses: [
            new OA\Response(response: 204, description: 'Deleted'),
            new OA\Response(response: 404, description: 'Not found'),
            new OA\Response(response: 409, description: 'Has active assignments'),
        ],
    )]
    public function destroy(string $id): JsonResponse
    {
        $this->service->softDelete($id);

        return response()->json(null, 204);
    }
}
