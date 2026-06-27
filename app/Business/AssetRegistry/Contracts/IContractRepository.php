<?php declare(strict_types=1);

namespace App\Business\AssetRegistry\Contracts;

use App\Business\AssetRegistry\Domain\Aggregates\ContractAggregate;
use App\Business\AssetRegistry\Domain\Entities\Contract;
use App\Business\AssetRegistry\Domain\Entities\ContractAsset;
use App\Business\AssetRegistry\Domain\Exceptions\ContractNotFoundException;

interface IContractRepository
{
    /** @throws ContractNotFoundException */
    public function findById(string $id): Contract;

    /** @throws ContractNotFoundException */
    public function findByIdWithAssets(string $id): ContractAggregate;

    /** @return Contract[] */
    public function findAll(): array;

    public function saveContract(Contract $contract): Contract;

    public function deleteContract(string $id): void;

    public function addContractAsset(ContractAsset $contractAsset): ContractAsset;

    public function removeContractAsset(string $contractId, string $assetId): void;

    public function isSerialNumberTaken(string $serialNumber): bool;
}
