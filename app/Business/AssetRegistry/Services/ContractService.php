<?php declare(strict_types=1);

namespace App\Business\AssetRegistry\Services;

use App\Business\AssetRegistry\Contracts\IAssetRepository;
use App\Business\AssetRegistry\Contracts\IContractRepository;
use App\Business\AssetRegistry\Contracts\IUuidGenerator;
use App\Business\AssetRegistry\Domain\Aggregates\ContractAggregate;
use App\Business\AssetRegistry\Domain\Entities\Contract;
use App\Business\AssetRegistry\Domain\Entities\ContractAsset;
use App\Business\AssetRegistry\Domain\Exceptions\SerialNumberTakenException;
use App\Business\AssetRegistry\DTOs\AssignAssetData;
use App\Business\AssetRegistry\DTOs\ContractAssetData;
use App\Business\AssetRegistry\DTOs\ContractData;
use App\Business\AssetRegistry\DTOs\ContractDetailData;
use App\Business\AssetRegistry\DTOs\CreateContractData;
use App\Business\AssetRegistry\DTOs\UpdateContractData;
use DateTimeImmutable;

final class ContractService
{
    public function __construct(
        private readonly IContractRepository $contractRepository,
        private readonly IAssetRepository $assetRepository,
        private readonly IUuidGenerator $uuidGenerator,
    ) {}

    public function create(CreateContractData $data): ContractData
    {
        $contract = new Contract(
            $this->uuidGenerator->generate(),
            $data->contractNumber,
            $data->clientName,
            new DateTimeImmutable($data->startDate),
            $data->endDate !== null ? new DateTimeImmutable($data->endDate) : null,
        );

        $saved = $this->contractRepository->saveContract($contract);

        return $this->toContractData($saved);
    }

    public function update(string $id, UpdateContractData $data): ContractData
    {
        $contract = $this->contractRepository->findById($id);

        $contract->update(
            $data->contractNumber,
            $data->clientName,
            new DateTimeImmutable($data->startDate),
            $data->endDate !== null ? new DateTimeImmutable($data->endDate) : null,
        );

        $saved = $this->contractRepository->saveContract($contract);

        return $this->toContractData($saved);
    }

    public function delete(string $id): void
    {
        $this->contractRepository->findById($id);
        $this->contractRepository->deleteContract($id);
    }

    public function findById(string $id): ContractData
    {
        return $this->toContractData($this->contractRepository->findById($id));
    }

    public function findByIdWithAssets(string $id): ContractDetailData
    {
        return $this->toDetailData($this->contractRepository->findByIdWithAssets($id));
    }

    /** @return ContractData[] */
    public function findAll(): array
    {
        return array_map($this->toContractData(...), $this->contractRepository->findAll());
    }

    public function assignAsset(string $contractId, AssignAssetData $data): ContractDetailData
    {
        $aggregate = $this->contractRepository->findByIdWithAssets($contractId);

        $this->assetRepository->findById($data->assetId);

        if ($this->contractRepository->isSerialNumberTaken($data->serialNumber)) {
            throw new SerialNumberTakenException($data->serialNumber);
        }

        $contractAsset = new ContractAsset(
            $this->uuidGenerator->generate(),
            $contractId,
            $data->assetId,
            $data->serialNumber,
        );

        $aggregate->assignAsset($contractAsset);

        $this->contractRepository->addContractAsset($contractAsset);

        return $this->toDetailData(
            $this->contractRepository->findByIdWithAssets($contractId),
        );
    }

    public function removeAsset(string $contractId, string $assetId): void
    {
        $aggregate = $this->contractRepository->findByIdWithAssets($contractId);
        $aggregate->removeAsset($assetId);
        $this->contractRepository->removeContractAsset($contractId, $assetId);
    }

    private function toContractData(Contract $contract): ContractData
    {
        return new ContractData(
            $contract->getId(),
            $contract->getContractNumber(),
            $contract->getClientName(),
            $contract->getStartDate()->format('Y-m-d'),
            $contract->getEndDate()?->format('Y-m-d'),
        );
    }

    private function toDetailData(ContractAggregate $aggregate): ContractDetailData
    {
        $contract = $aggregate->getContract();

        $assets = array_map(
            function (ContractAsset $ca) use ($aggregate): ContractAssetData {
                $asset = $aggregate->getAssetDetail($ca->getAssetId());

                return new ContractAssetData(
                    $ca->getId(),
                    $ca->getAssetId(),
                    $ca->getSerialNumber(),
                    $asset?->getName() ?? '',
                    $asset?->getManufacturer() ?? '',
                    $asset?->getModel() ?? '',
                );
            },
            $aggregate->getContractAssets(),
        );

        return new ContractDetailData(
            $contract->getId(),
            $contract->getContractNumber(),
            $contract->getClientName(),
            $contract->getStartDate()->format('Y-m-d'),
            $contract->getEndDate()?->format('Y-m-d'),
            $assets,
        );
    }
}
