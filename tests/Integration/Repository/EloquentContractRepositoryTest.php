<?php declare(strict_types=1);

namespace Tests\Integration\Repository;

use App\Business\AssetRegistry\Domain\Entities\Contract;
use App\Business\AssetRegistry\Domain\Entities\ContractAsset;
use App\Business\AssetRegistry\Domain\Exceptions\ContractNotFoundException;
use App\Repository\Eloquent\Models\AssetModel;
use App\Repository\Eloquent\Models\ContractAssetModel;
use App\Repository\Eloquent\Models\ContractModel;
use App\Repository\Eloquent\Repositories\EloquentContractRepository;
use DateTimeImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EloquentContractRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private EloquentContractRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new EloquentContractRepository();
    }

    public function testSaveContractCreatesNew(): void
    {
        $contract = new Contract(
            'c-uuid-1234-5678-abcd-ef0123456789',
            'C-001',
            'Acme Corp',
            new DateTimeImmutable('2026-01-01'),
        );

        $saved = $this->repository->saveContract($contract);

        $this->assertSame('C-001', $saved->getContractNumber());
        $this->assertDatabaseHas('contracts', ['contract_number' => 'C-001']);
    }

    public function testFindByIdReturnsContract(): void
    {
        ContractModel::create([
            'id' => 'c-uuid-1234-5678-abcd-ef0123456789',
            'contract_number' => 'C-001',
            'client_name' => 'Acme',
            'start_date' => '2026-01-01',
        ]);

        $contract = $this->repository->findById('c-uuid-1234-5678-abcd-ef0123456789');

        $this->assertSame('C-001', $contract->getContractNumber());
    }

    public function testFindByIdThrowsWhenNotFound(): void
    {
        $this->expectException(ContractNotFoundException::class);
        $this->repository->findById('non-existent');
    }

    public function testFindByIdWithAssetsLoadsRelations(): void
    {
        ContractModel::create([
            'id' => 'c-uuid',
            'contract_number' => 'C-001',
            'client_name' => 'Acme',
            'start_date' => '2026-01-01',
        ]);
        AssetModel::create([
            'id' => 'a-uuid',
            'name' => 'Server X',
            'manufacturer' => 'Dell',
            'model' => 'Model Y',
        ]);
        ContractAssetModel::create([
            'id' => 'ca-uuid',
            'contract_id' => 'c-uuid',
            'asset_id' => 'a-uuid',
            'serial_number' => 'SN-001',
        ]);

        $aggregate = $this->repository->findByIdWithAssets('c-uuid');

        $this->assertCount(1, $aggregate->getContractAssets());
        $this->assertSame('SN-001', $aggregate->getContractAssets()[0]->getSerialNumber());
        $this->assertSame('Server X', $aggregate->getAssetDetail('a-uuid')?->getName());
    }

    public function testDeleteContractCascadesContractAssets(): void
    {
        ContractModel::create([
            'id' => 'c-uuid',
            'contract_number' => 'C-001',
            'client_name' => 'Acme',
            'start_date' => '2026-01-01',
        ]);
        AssetModel::create([
            'id' => 'a-uuid',
            'name' => 'Server X',
            'manufacturer' => 'Dell',
            'model' => 'Model Y',
        ]);
        ContractAssetModel::create([
            'id' => 'ca-uuid',
            'contract_id' => 'c-uuid',
            'asset_id' => 'a-uuid',
            'serial_number' => 'SN-001',
        ]);

        $this->repository->deleteContract('c-uuid');

        $this->assertDatabaseMissing('contracts', ['id' => 'c-uuid']);
        $this->assertDatabaseMissing('contract_assets', ['contract_id' => 'c-uuid']);
        $this->assertDatabaseHas('assets', ['id' => 'a-uuid']);
    }

    public function testIsSerialNumberTakenReturnsTrueWhenExists(): void
    {
        ContractModel::create([
            'id' => 'c-uuid',
            'contract_number' => 'C-001',
            'client_name' => 'Acme',
            'start_date' => '2026-01-01',
        ]);
        AssetModel::create(['id' => 'a-uuid', 'name' => 'S', 'manufacturer' => 'M', 'model' => 'X']);
        ContractAssetModel::create([
            'id' => 'ca-uuid',
            'contract_id' => 'c-uuid',
            'asset_id' => 'a-uuid',
            'serial_number' => 'SN-001',
        ]);

        $this->assertTrue($this->repository->isSerialNumberTaken('SN-001'));
        $this->assertFalse($this->repository->isSerialNumberTaken('SN-999'));
    }

    public function testAddAndRemoveContractAsset(): void
    {
        ContractModel::create([
            'id' => 'c-uuid',
            'contract_number' => 'C-001',
            'client_name' => 'Acme',
            'start_date' => '2026-01-01',
        ]);
        AssetModel::create(['id' => 'a-uuid', 'name' => 'S', 'manufacturer' => 'M', 'model' => 'X']);

        $ca = new ContractAsset('ca-uuid', 'c-uuid', 'a-uuid', 'SN-001');
        $this->repository->addContractAsset($ca);

        $this->assertDatabaseHas('contract_assets', ['serial_number' => 'SN-001']);

        $this->repository->removeContractAsset('c-uuid', 'a-uuid');

        $this->assertDatabaseMissing('contract_assets', ['serial_number' => 'SN-001']);
    }
}
