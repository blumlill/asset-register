<?php declare(strict_types=1);

namespace Tests\Integration\Repository;

use App\Business\AssetRegistry\Domain\Entities\Asset;
use App\Business\AssetRegistry\Domain\Exceptions\AssetNotFoundException;
use App\Repository\Eloquent\Models\AssetModel;
use App\Repository\Eloquent\Models\ContractAssetModel;
use App\Repository\Eloquent\Models\ContractModel;
use App\Repository\Eloquent\Repositories\EloquentAssetRepository;
use DateTimeImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EloquentAssetRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private EloquentAssetRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new EloquentAssetRepository();
    }

    public function testSaveCreatesNewAsset(): void
    {
        $asset = new Asset('test-uuid-1234-5678-abcd-ef0123456789', 'Server X', 'Dell', 'Model R750');

        $saved = $this->repository->save($asset);

        $this->assertSame('test-uuid-1234-5678-abcd-ef0123456789', $saved->getId());
        $this->assertSame('Server X', $saved->getName());
        $this->assertDatabaseHas('assets', ['id' => 'test-uuid-1234-5678-abcd-ef0123456789', 'name' => 'Server X']);
    }

    public function testFindByIdReturnsAsset(): void
    {
        AssetModel::create([
            'id' => 'test-uuid-1234-5678-abcd-ef0123456789',
            'name' => 'Server X',
            'manufacturer' => 'Dell',
            'model' => 'Model R750',
        ]);

        $asset = $this->repository->findById('test-uuid-1234-5678-abcd-ef0123456789');

        $this->assertSame('Server X', $asset->getName());
    }

    public function testFindByIdThrowsWhenNotFound(): void
    {
        $this->expectException(AssetNotFoundException::class);
        $this->repository->findById('non-existent-uuid');
    }

    public function testFindAllReturnsOnlyActiveAssets(): void
    {
        AssetModel::create(['id' => 'uuid-1', 'name' => 'A', 'manufacturer' => 'M', 'model' => 'X']);
        AssetModel::create(['id' => 'uuid-2', 'name' => 'B', 'manufacturer' => 'M', 'model' => 'Y']);

        $asset = AssetModel::find('uuid-2');
        $asset->delete();

        $assets = $this->repository->findAll();

        $this->assertCount(1, $assets);
        $this->assertSame('uuid-1', $assets[0]->getId());
    }

    public function testSaveSoftDeletesAsset(): void
    {
        $model = AssetModel::create([
            'id' => 'test-uuid-1234-5678-abcd-ef0123456789',
            'name' => 'Server X',
            'manufacturer' => 'Dell',
            'model' => 'Model R750',
        ]);

        $asset = new Asset('test-uuid-1234-5678-abcd-ef0123456789', 'Server X', 'Dell', 'Model R750');
        $asset->softDelete(new DateTimeImmutable());

        $this->repository->save($asset);

        $this->assertSoftDeleted('assets', ['id' => 'test-uuid-1234-5678-abcd-ef0123456789']);
    }

    public function testHasActiveAssignmentsReturnsTrueWhenAssigned(): void
    {
        AssetModel::create(['id' => 'asset-uuid', 'name' => 'A', 'manufacturer' => 'M', 'model' => 'X']);
        ContractModel::create([
            'id' => 'contract-uuid',
            'contract_number' => 'C-001',
            'client_name' => 'Corp',
            'start_date' => '2026-01-01',
        ]);
        ContractAssetModel::create([
            'id' => 'ca-uuid',
            'contract_id' => 'contract-uuid',
            'asset_id' => 'asset-uuid',
            'serial_number' => 'SN-001',
        ]);

        $this->assertTrue($this->repository->hasActiveAssignments('asset-uuid'));
    }

    public function testHasActiveAssignmentsReturnsFalseWhenNone(): void
    {
        AssetModel::create(['id' => 'asset-uuid', 'name' => 'A', 'manufacturer' => 'M', 'model' => 'X']);

        $this->assertFalse($this->repository->hasActiveAssignments('asset-uuid'));
    }
}
