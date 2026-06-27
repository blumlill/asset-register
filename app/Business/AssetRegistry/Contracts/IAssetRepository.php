<?php declare(strict_types=1);

namespace App\Business\AssetRegistry\Contracts;

use App\Business\AssetRegistry\Domain\Entities\Asset;
use App\Business\AssetRegistry\Domain\Exceptions\AssetNotFoundException;

interface IAssetRepository
{
    /** @throws AssetNotFoundException */
    public function findById(string $id): Asset;

    /** @return Asset[] */
    public function findAll(): array;

    public function save(Asset $asset): Asset;

    public function hasActiveAssignments(string $assetId): bool;
}
