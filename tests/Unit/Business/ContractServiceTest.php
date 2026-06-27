<?php declare(strict_types=1);

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
use App\Business\AssetRegistry\DTOs\CreateContractData;
use App\Business\AssetRegistry\DTOs\UpdateContractData;
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

    public function testCreate(): void
    {
        $dto = new CreateContractData('C-001', 'Acme Corp', '2026-01-01', null);

        $this->contractRepository
            ->expects($this->once())
            ->method('saveContract')
            ->willReturnCallback(fn (Contract $c) => $c);

        $result = $this->service->create($dto);

        $this->assertSame('generated-uuid', $result->id);
        $this->assertSame('C-001', $result->contractNumber);
        $this->assertSame('Acme Corp', $result->clientName);
        $this->assertSame('2026-01-01', $result->startDate);
        $this->assertNull($result->endDate);
    }

    public function testUpdate(): void
    {
        $contract = new Contract('c-uuid', 'C-001', 'Old Name', new DateTimeImmutable('2026-01-01'));
        $dto = new UpdateContractData('C-002', 'New Name', '2026-06-01', '2026-12-31');

        $this->contractRepository->method('findById')->willReturn($contract);
        $this->contractRepository->expects($this->once())->method('saveContract')
            ->willReturnCallback(fn (Contract $c) => $c);

        $result = $this->service->update('c-uuid', $dto);

        $this->assertSame('C-002', $result->contractNumber);
        $this->assertSame('New Name', $result->clientName);
        $this->assertSame('2026-12-31', $result->endDate);
    }

    public function testUpdateThrowsWhenNotFound(): void
    {
        $this->contractRepository->method('findById')
            ->willThrowException(new ContractNotFoundException('c-uuid'));

        $this->expectException(ContractNotFoundException::class);
        $this->service->update('c-uuid', new UpdateContractData('C-001', 'Corp', '2026-01-01', null));
    }

    public function testDelete(): void
    {
        $contract = new Contract('c-uuid', 'C-001', 'Corp', new DateTimeImmutable('2026-01-01'));
        $this->contractRepository->method('findById')->willReturn($contract);
        $this->contractRepository->expects($this->once())->method('deleteContract')->with('c-uuid');

        $this->service->delete('c-uuid');
    }

    public function testFindByIdWithAssets(): void
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

    public function testAssignAsset(): void
    {
        $contract = new Contract('c-uuid', 'C-001', 'Acme', new DateTimeImmutable('2026-01-01'));
        $emptyAggregate = new ContractAggregate($contract);
        $asset = new Asset('a-uuid', 'Server X', 'Dell', 'Model Y');

        $ca = new ContractAsset('generated-uuid', 'c-uuid', 'a-uuid', 'SN-001');
        $filledAggregate = new ContractAggregate($contract, [$ca], [$asset]);

        $this->contractRepository->method('findByIdWithAssets')
            ->willReturnOnConsecutiveCalls($emptyAggregate, $filledAggregate);
        $this->assetRepository->method('findById')->willReturn($asset);
        $this->contractRepository->method('isSerialNumberTaken')->willReturn(false);
        $this->contractRepository->expects($this->once())->method('addContractAsset')
            ->willReturnCallback(fn (ContractAsset $ca) => $ca);

        $result = $this->service->assignAsset('c-uuid', new AssignAssetData('a-uuid', 'SN-001'));

        $this->assertCount(1, $result->assets);
        $this->assertSame('SN-001', $result->assets[0]->serialNumber);
    }

    public function testAssignAssetThrowsWhenSerialNumberTaken(): void
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

    public function testAssignAssetThrowsWhenAssetAlreadyAssigned(): void
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

    public function testAssignAssetThrowsWhenAssetNotFound(): void
    {
        $contract = new Contract('c-uuid', 'C-001', 'Acme', new DateTimeImmutable('2026-01-01'));
        $aggregate = new ContractAggregate($contract);

        $this->contractRepository->method('findByIdWithAssets')->willReturn($aggregate);
        $this->assetRepository->method('findById')
            ->willThrowException(new AssetNotFoundException('a-uuid'));

        $this->expectException(AssetNotFoundException::class);
        $this->service->assignAsset('c-uuid', new AssignAssetData('a-uuid', 'SN-001'));
    }

    public function testRemoveAsset(): void
    {
        $contract = new Contract('c-uuid', 'C-001', 'Acme', new DateTimeImmutable('2026-01-01'));
        $ca = new ContractAsset('ca-uuid', 'c-uuid', 'a-uuid', 'SN-001');
        $aggregate = new ContractAggregate($contract, [$ca]);

        $this->contractRepository->method('findByIdWithAssets')->willReturn($aggregate);
        $this->contractRepository->expects($this->once())->method('removeContractAsset')
            ->with('c-uuid', 'a-uuid');

        $this->service->removeAsset('c-uuid', 'a-uuid');
    }
}
