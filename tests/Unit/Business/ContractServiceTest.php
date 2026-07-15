<?php

declare(strict_types=1);

namespace Tests\Unit\Business;

use App\Business\AssetRegistry\Contracts\IAssetRepository;
use App\Business\AssetRegistry\Contracts\IContractRepository;
use App\Business\AssetRegistry\Contracts\IUuidGenerator;
use App\Business\AssetRegistry\Domain\Aggregates\ContractAggregate;
use App\Business\AssetRegistry\Domain\Entities\Asset;
use App\Business\AssetRegistry\Domain\Entities\Contract;
use App\Business\AssetRegistry\Domain\Entities\ContractAsset;
use App\Business\AssetRegistry\Domain\Exceptions\AssetAlreadyAssignedException;
use App\Business\AssetRegistry\Domain\Exceptions\AssetNotFoundException;
use App\Business\AssetRegistry\Domain\Exceptions\ContractNotFoundException;
use App\Business\AssetRegistry\Domain\Exceptions\SerialNumberTakenException;
use App\Business\AssetRegistry\DTOs\AssignAssetData;
use App\Business\AssetRegistry\DTOs\ContractInputData;
use App\Business\AssetRegistry\Services\ContractService;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class ContractServiceTest extends TestCase
{
    private IContractRepository&MockObject $contractRepository;

    private IAssetRepository&MockObject $assetRepository;

    private IUuidGenerator&MockObject $uuidGenerator;

    private ContractService $service;

    protected function setUp(): void
    {
        $this->contractRepository = $this->createMock(IContractRepository::class);
        $this->assetRepository = $this->createMock(IAssetRepository::class);
        $this->uuidGenerator = $this->createMock(IUuidGenerator::class);
        $this->uuidGenerator->method('generate')->willReturn('generated-uuid');

        $this->service = new ContractService(
            $this->contractRepository,
            $this->assetRepository,
            $this->uuidGenerator,
        );
    }

    public function test_create(): void
    {
        $dto = new ContractInputData('C-001', 'Acme Corp', '2026-01-01', null);

        $this->contractRepository
            ->expects($this->once())
            ->method('save')
            ->willReturnCallback(fn (Contract $c) => $c);

        $result = $this->service->create($dto);

        $this->assertSame('generated-uuid', $result->id);
        $this->assertSame('C-001', $result->contractNumber);
        $this->assertSame('Acme Corp', $result->clientName);
        $this->assertSame('2026-01-01', $result->startDate);
        $this->assertNull($result->endDate);
    }

    public function test_update(): void
    {
        $contract = new Contract('c-uuid', 'C-001', 'Old Name', new DateTimeImmutable('2026-01-01'));
        $dto = new ContractInputData('C-002', 'New Name', '2026-06-01', '2026-12-31');

        $this->contractRepository->method('findById')->willReturn($contract);
        $this->contractRepository->expects($this->once())->method('save')
            ->willReturnCallback(fn (Contract $c) => $c);

        $result = $this->service->update('c-uuid', $dto);

        $this->assertSame('C-002', $result->contractNumber);
        $this->assertSame('New Name', $result->clientName);
        $this->assertSame('2026-12-31', $result->endDate);
    }

    public function test_update_throws_when_not_found(): void
    {
        $this->contractRepository->method('findById')
            ->willThrowException(new ContractNotFoundException('c-uuid'));

        $this->expectException(ContractNotFoundException::class);
        $this->service->update('c-uuid', new ContractInputData('C-001', 'Corp', '2026-01-01', null));
    }

    public function test_delete(): void
    {
        $this->contractRepository->expects($this->once())->method('delete')->with('c-uuid');

        $this->service->delete('c-uuid');
    }

    public function test_find_by_id_with_assets(): void
    {
        $contract = new Contract('c-uuid', 'C-001', 'Acme', new DateTimeImmutable('2026-01-01'));
        $ca = new ContractAsset('ca-uuid', 'c-uuid', 'a-uuid', 'SN-001');
        $asset = new Asset('a-uuid', 'Server X', 'Dell', 'Model Y');
        $aggregate = new ContractAggregate($contract, [$ca], [$asset]);

        $this->contractRepository->method('findByIdWithAssets')->willReturn($aggregate);

        $result = $this->service->findByIdWithAssets('c-uuid');

        $this->assertSame('c-uuid', $result->id);
        $this->assertCount(1, $result->assets);
        $this->assertSame('SN-001', $result->assets[0]->serialNumber);
        $this->assertSame('Server X', $result->assets[0]->assetName);
    }

    public function test_assign_asset(): void
    {
        $contract = new Contract('c-uuid', 'C-001', 'Acme', new DateTimeImmutable('2026-01-01'));
        $aggregate = new ContractAggregate($contract);
        $asset = new Asset('a-uuid', 'Server X', 'Dell', 'Model Y');

        $this->contractRepository->method('findByIdWithAssets')->willReturn($aggregate);
        $this->assetRepository->method('findById')->willReturn($asset);
        $this->contractRepository->method('isSerialNumberTaken')->willReturn(false);
        $this->contractRepository->expects($this->once())->method('addContractAsset')
            ->willReturnCallback(fn (ContractAsset $ca) => $ca);

        $result = $this->service->assignAsset('c-uuid', new AssignAssetData('a-uuid', 'SN-001'));

        $this->assertCount(1, $result->assets);
        $this->assertSame('SN-001', $result->assets[0]->serialNumber);
        $this->assertSame('Server X', $result->assets[0]->assetName);
    }

    public function test_assign_asset_throws_when_serial_number_taken(): void
    {
        $contract = new Contract('c-uuid', 'C-001', 'Acme', new DateTimeImmutable('2026-01-01'));
        $aggregate = new ContractAggregate($contract);
        $asset = new Asset('a-uuid', 'Server X', 'Dell', 'Model Y');

        $this->contractRepository->method('findByIdWithAssets')->willReturn($aggregate);
        $this->assetRepository->method('findById')->willReturn($asset);
        $this->contractRepository->method('isSerialNumberTaken')->willReturn(true);

        $this->expectException(SerialNumberTakenException::class);
        $this->service->assignAsset('c-uuid', new AssignAssetData('a-uuid', 'SN-TAKEN'));
    }

    public function test_assign_asset_throws_when_asset_already_assigned(): void
    {
        $contract = new Contract('c-uuid', 'C-001', 'Acme', new DateTimeImmutable('2026-01-01'));
        $existingCa = new ContractAsset('ca-uuid', 'c-uuid', 'a-uuid', 'SN-EXISTING');
        $aggregate = new ContractAggregate($contract, [$existingCa]);
        $asset = new Asset('a-uuid', 'Server X', 'Dell', 'Model Y');

        $this->contractRepository->method('findByIdWithAssets')->willReturn($aggregate);
        $this->assetRepository->method('findById')->willReturn($asset);
        $this->contractRepository->method('isSerialNumberTaken')->willReturn(false);

        $this->expectException(AssetAlreadyAssignedException::class);
        $this->service->assignAsset('c-uuid', new AssignAssetData('a-uuid', 'SN-NEW'));
    }

    public function test_assign_asset_throws_when_asset_not_found(): void
    {
        $contract = new Contract('c-uuid', 'C-001', 'Acme', new DateTimeImmutable('2026-01-01'));
        $aggregate = new ContractAggregate($contract);

        $this->contractRepository->method('findByIdWithAssets')->willReturn($aggregate);
        $this->assetRepository->method('findById')
            ->willThrowException(new AssetNotFoundException('a-uuid'));

        $this->expectException(AssetNotFoundException::class);
        $this->service->assignAsset('c-uuid', new AssignAssetData('a-uuid', 'SN-001'));
    }

    public function test_remove_asset(): void
    {
        $contract = new Contract('c-uuid', 'C-001', 'Acme', new DateTimeImmutable('2026-01-01'));

        $this->contractRepository->method('findById')->willReturn($contract);
        $this->contractRepository->expects($this->once())->method('removeContractAsset')
            ->with('c-uuid', 'a-uuid');

        $this->service->removeAsset('c-uuid', 'a-uuid');
    }
}
