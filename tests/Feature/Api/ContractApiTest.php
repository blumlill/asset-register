<?php declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Repository\Eloquent\Models\AssetModel;
use App\Repository\Eloquent\Models\ContractAssetModel;
use App\Repository\Eloquent\Models\ContractModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContractApiTest extends TestCase
{
    use RefreshDatabase;

    public function testListContractsReturnsEmptyArray(): void
    {
        $response = $this->getJson('/api/v1/contracts');

        $response->assertOk()->assertJsonStructure(['data']);
        $this->assertEmpty($response->json('data'));
    }

    public function testCreateContractReturns201(): void
    {
        $response = $this->postJson('/api/v1/contracts', [
            'contract_number' => 'C-2026-001',
            'client_name' => 'Acme Corp',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.contract_number', 'C-2026-001')
            ->assertJsonPath('data.client_name', 'Acme Corp')
            ->assertJsonPath('data.start_date', '2026-01-01')
            ->assertJsonPath('data.end_date', '2026-12-31');

        $this->assertDatabaseHas('contracts', ['contract_number' => 'C-2026-001']);
    }

    public function testCreateContractValidationFailsWithDuplicateNumber(): void
    {
        ContractModel::create([
            'id' => 'c1d2e3f4-0000-0000-0000-000000000001',
            'contract_number' => 'C-001',
            'client_name' => 'Corp',
            'start_date' => '2026-01-01',
        ]);

        $response = $this->postJson('/api/v1/contracts', [
            'contract_number' => 'C-001',
            'client_name' => 'New Corp',
            'start_date' => '2026-01-01',
        ]);

        $response->assertUnprocessable()->assertJsonPath('error.code', 'VALIDATION_ERROR');
    }

    public function testShowContractReturnsDetailWithAssets(): void
    {
        $contract = ContractModel::create([
            'id' => 'c1d2e3f4-0000-0000-0000-000000000001',
            'contract_number' => 'C-001',
            'client_name' => 'Acme',
            'start_date' => '2026-01-01',
        ]);
        $asset = AssetModel::create([
            'id' => 'a1b2c3d4-0000-0000-0000-000000000001',
            'name' => 'Server X',
            'manufacturer' => 'Dell',
            'model' => 'PowerEdge',
        ]);
        ContractAssetModel::create([
            'id' => 'ca000000-0000-0000-0000-000000000001',
            'contract_id' => $contract->id,
            'asset_id' => $asset->id,
            'serial_number' => 'SN-001',
        ]);

        $response = $this->getJson("/api/v1/contracts/{$contract->id}");

        $response->assertOk()
            ->assertJsonPath('data.contract_number', 'C-001')
            ->assertJsonCount(1, 'data.assets')
            ->assertJsonPath('data.assets.0.serial_number', 'SN-001')
            ->assertJsonPath('data.assets.0.asset_name', 'Server X');
    }

    public function testShowContractReturns404WhenNotFound(): void
    {
        $response = $this->getJson('/api/v1/contracts/non-existent');

        $response->assertNotFound()->assertJsonPath('error.code', 'CONTRACT_NOT_FOUND');
    }

    public function testUpdateContractReturns200(): void
    {
        $contract = ContractModel::create([
            'id' => 'c1d2e3f4-0000-0000-0000-000000000001',
            'contract_number' => 'C-OLD',
            'client_name' => 'Old Name',
            'start_date' => '2026-01-01',
        ]);

        $response = $this->putJson("/api/v1/contracts/{$contract->id}", [
            'contract_number' => 'C-NEW',
            'client_name' => 'New Name',
            'start_date' => '2026-06-01',
        ]);

        $response->assertOk()->assertJsonPath('data.contract_number', 'C-NEW');
        $this->assertDatabaseHas('contracts', ['id' => $contract->id, 'client_name' => 'New Name']);
    }

    public function testDeleteContractReturns204AndCascades(): void
    {
        $contract = ContractModel::create([
            'id' => 'c1d2e3f4-0000-0000-0000-000000000001',
            'contract_number' => 'C-001',
            'client_name' => 'Acme',
            'start_date' => '2026-01-01',
        ]);
        $asset = AssetModel::create([
            'id' => 'a1b2c3d4-0000-0000-0000-000000000001',
            'name' => 'Server X',
            'manufacturer' => 'Dell',
            'model' => 'PowerEdge',
        ]);
        ContractAssetModel::create([
            'id' => 'ca000000-0000-0000-0000-000000000001',
            'contract_id' => $contract->id,
            'asset_id' => $asset->id,
            'serial_number' => 'SN-001',
        ]);

        $response = $this->deleteJson("/api/v1/contracts/{$contract->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('contracts', ['id' => $contract->id]);
        $this->assertDatabaseMissing('contract_assets', ['contract_id' => $contract->id]);
        $this->assertDatabaseHas('assets', ['id' => $asset->id]);
    }
}
