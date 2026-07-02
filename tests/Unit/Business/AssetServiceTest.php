<?php

declare(strict_types=1);

namespace Tests\Unit\Business;

use App\Business\AssetRegistry\Contracts\IAssetRepository;
use App\Business\AssetRegistry\Contracts\IUuidGenerator;
use App\Business\AssetRegistry\Domain\Entities\Asset;
use App\Business\AssetRegistry\Domain\Exceptions\AssetHasActiveAssignmentsException;
use App\Business\AssetRegistry\Domain\Exceptions\AssetNotFoundException;
use App\Business\AssetRegistry\DTOs\CreateAssetData;
use App\Business\AssetRegistry\DTOs\UpdateAssetData;
use App\Business\AssetRegistry\Services\AssetService;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class AssetServiceTest extends TestCase
{
    private IAssetRepository&MockObject $repository;

    private IUuidGenerator&MockObject $uuidGenerator;

    private AssetService $service;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(IAssetRepository::class);
        $this->uuidGenerator = $this->createMock(IUuidGenerator::class);
        $this->uuidGenerator->method('generate')->willReturn('generated-uuid');
        $this->service = new AssetService($this->repository, $this->uuidGenerator);
    }

    public function test_create(): void
    {
        $dto = new CreateAssetData('Server X', 'Dell', 'PowerEdge R750');

        $this->repository
            ->expects($this->once())
            ->method('save')
            ->willReturnCallback(fn (Asset $a) => $a);

        $result = $this->service->create($dto);

        $this->assertSame('generated-uuid', $result->id);
        $this->assertSame('Server X', $result->name);
        $this->assertSame('Dell', $result->manufacturer);
        $this->assertSame('PowerEdge R750', $result->model);
    }

    public function test_update(): void
    {
        $asset = new Asset('asset-uuid', 'Old Name', 'Dell', 'Old Model');
        $dto = new UpdateAssetData('New Name', 'HP', 'New Model');

        $this->repository->method('findById')->willReturn($asset);
        $this->repository->expects($this->once())->method('save')->willReturnCallback(fn (Asset $a) => $a);

        $result = $this->service->update('asset-uuid', $dto);

        $this->assertSame('New Name', $result->name);
        $this->assertSame('HP', $result->manufacturer);
        $this->assertSame('New Model', $result->model);
    }

    public function test_update_throws_when_not_found(): void
    {
        $this->repository->method('findById')
            ->willThrowException(new AssetNotFoundException('asset-uuid'));

        $this->expectException(AssetNotFoundException::class);
        $this->service->update('asset-uuid', new UpdateAssetData('x', 'y', 'z'));
    }

    public function test_soft_delete(): void
    {
        $asset = new Asset('asset-uuid', 'Server X', 'Dell', 'Model');

        $this->repository->method('findById')->willReturn($asset);
        $this->repository->method('hasActiveAssignments')->willReturn(false);
        $this->repository->expects($this->once())->method('save')
            ->willReturnCallback(fn (Asset $a) => $a);

        $this->service->softDelete('asset-uuid');

        $this->assertTrue($asset->isDeleted());
    }

    public function test_soft_delete_throws_when_has_active_assignments(): void
    {
        $asset = new Asset('asset-uuid', 'Server X', 'Dell', 'Model');

        $this->repository->method('findById')->willReturn($asset);
        $this->repository->method('hasActiveAssignments')->willReturn(true);

        $this->expectException(AssetHasActiveAssignmentsException::class);
        $this->service->softDelete('asset-uuid');
    }

    public function test_find_by_id(): void
    {
        $asset = new Asset('asset-uuid', 'Server X', 'Dell', 'Model');
        $this->repository->method('findById')->willReturn($asset);

        $result = $this->service->findById('asset-uuid');

        $this->assertSame('asset-uuid', $result->id);
        $this->assertSame('Server X', $result->name);
    }

    public function test_find_all(): void
    {
        $assets = [
            new Asset('uuid-1', 'Server A', 'Dell', 'Model A'),
            new Asset('uuid-2', 'Server B', 'HP', 'Model B'),
        ];
        $this->repository->method('findAll')->willReturn($assets);

        $result = $this->service->findAll();

        $this->assertCount(2, $result);
        $this->assertSame('uuid-1', $result[0]->id);
        $this->assertSame('uuid-2', $result[1]->id);
    }
}
