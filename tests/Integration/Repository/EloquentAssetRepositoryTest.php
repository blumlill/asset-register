<?php

declare(strict_types=1);

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
        $this->repository = new EloquentAssetRepository;
    }

    public function test_save_creates_new_asset(): void
    {
        $asset = new Asset('019f19a0-94ea-7178-80fc-67f72e3bd2b3', 'Server X', 'Dell', 'Model R750');

        $saved = $this->repository->save($asset);

        $this->assertSame('019f19a0-94ea-7178-80fc-67f72e3bd2b3', $saved->id);
        $this->assertSame('Server X', $saved->getName());
        $this->assertDatabaseHas('assets', ['id' => '019f19a0-94ea-7178-80fc-67f72e3bd2b3', 'name' => 'Server X']);
    }

    public function test_find_by_id_returns_asset(): void
    {
        AssetModel::create([
            'id' => '019f19a0-94ea-7178-80fc-67f72e3bd2b3',
            'name' => 'Server X',
            'manufacturer' => 'Dell',
            'model' => 'Model R750',
        ]);

        $asset = $this->repository->findById('019f19a0-94ea-7178-80fc-67f72e3bd2b3');

        $this->assertSame('Server X', $asset->getName());
    }

    public function test_find_by_id_throws_when_not_found(): void
    {
        $this->expectException(AssetNotFoundException::class);
        $this->repository->findById('non-existent-uuid');
    }

    public function test_find_all_returns_only_active_assets(): void
    {
        AssetModel::create(['id' => 'uuid-1', 'name' => 'A', 'manufacturer' => 'M', 'model' => 'X']);
        AssetModel::create(['id' => 'uuid-2', 'name' => 'B', 'manufacturer' => 'M', 'model' => 'Y']);

        $asset = AssetModel::find('uuid-2');
        $asset->delete();

        $assets = $this->repository->findAll();

        $this->assertCount(1, $assets);
        $this->assertSame('uuid-1', $assets[0]->id);
    }

    public function test_save_soft_deletes_asset(): void
    {
        $model = AssetModel::create([
            'id' => '019f19a0-94ea-7178-80fc-67f72e3bd2b3',
            'name' => 'Server X',
            'manufacturer' => 'Dell',
            'model' => 'Model R750',
        ]);

        $asset = new Asset('019f19a0-94ea-7178-80fc-67f72e3bd2b3', 'Server X', 'Dell', 'Model R750');
        $asset->softDelete(new DateTimeImmutable);

        $this->repository->save($asset);

        $this->assertSoftDeleted('assets', ['id' => '019f19a0-94ea-7178-80fc-67f72e3bd2b3']);
    }

    public function test_has_active_assignments_returns_true_when_assigned(): void
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

    public function test_has_active_assignments_returns_false_when_none(): void
    {
        AssetModel::create(['id' => 'asset-uuid', 'name' => 'A', 'manufacturer' => 'M', 'model' => 'X']);

        $this->assertFalse($this->repository->hasActiveAssignments('asset-uuid'));
    }
}
