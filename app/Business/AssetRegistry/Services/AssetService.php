<?php

declare(strict_types=1);

namespace App\Business\AssetRegistry\Services;

use App\Business\AssetRegistry\Contracts\IAssetRepository;
use App\Business\AssetRegistry\Contracts\IUuidGenerator;
use App\Business\AssetRegistry\Domain\Entities\Asset;
use App\Business\AssetRegistry\Domain\Exceptions\AssetHasActiveAssignmentsException;
use App\Business\AssetRegistry\DTOs\AssetData;
use App\Business\AssetRegistry\DTOs\CreateAssetData;
use App\Business\AssetRegistry\DTOs\UpdateAssetData;
use DateTimeImmutable;

final class AssetService
{
    public function __construct(
        private readonly IAssetRepository $repository,
        private readonly IUuidGenerator $uuidGenerator,
    ) {}

    public function create(CreateAssetData $data): AssetData
    {
        $asset = new Asset(
            $this->uuidGenerator->generate(),
            $data->name,
            $data->manufacturer,
            $data->model,
        );

        $saved = $this->repository->save($asset);

        return $this->toData($saved);
    }

    public function update(string $id, UpdateAssetData $data): AssetData
    {
        $asset = $this->repository->findById($id);
        $asset->update($data->name, $data->manufacturer, $data->model);
        $saved = $this->repository->save($asset);

        return $this->toData($saved);
    }

    public function softDelete(string $id): void
    {
        $asset = $this->repository->findById($id);

        if ($this->repository->hasActiveAssignments($id)) {
            throw new AssetHasActiveAssignmentsException($id);
        }

        $asset->softDelete(new DateTimeImmutable);
        $this->repository->save($asset);
    }

    public function findById(string $id): AssetData
    {
        return $this->toData($this->repository->findById($id));
    }

    /** @return AssetData[] */
    public function findAll(): array
    {
        return array_map($this->toData(...), $this->repository->findAll());
    }

    private function toData(Asset $asset): AssetData
    {
        return new AssetData(
            $asset->id,
            $asset->getName(),
            $asset->getManufacturer(),
            $asset->getModel(),
        );
    }
}
