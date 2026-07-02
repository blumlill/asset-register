<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Repository\Eloquent\Models\AssetModel;
use App\Repository\Eloquent\Models\ContractAssetModel;
use App\Repository\Eloquent\Models\ContractModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContractAssetApiTest extends TestCase
{
    use RefreshDatabase;

    private ContractModel $contract;

    private AssetModel $asset;

    protected function setUp(): void
    {
        parent::setUp();

        $this->contract = ContractModel::create([
            'id' => 'c1d2e3f4-0000-0000-0000-000000000001',
            'contract_number' => 'C-001',
            'client_name' => 'Acme',
            'start_date' => '2026-01-01',
        ]);

        $this->asset = AssetModel::create([
            'id' => 'a1b2c3d4-0000-0000-0000-000000000001',
            'name' => 'Server X',
            'manufacturer' => 'Dell',
            'model' => 'PowerEdge',
        ]);
    }

    public function test_assign_asset_returns201(): void
    {
        $response = $this->postJson("/api/v1/contracts/{$this->contract->id}/assets", [
            'asset_id' => $this->asset->id,
            'serial_number' => 'SN-001',
        ]);

        $response->assertStatus(201)
            ->assertJsonCount(1, 'data.assets')
            ->assertJsonPath('data.assets.0.serial_number', 'SN-001')
            ->assertJsonPath('data.assets.0.asset_name', 'Server X');

        $this->assertDatabaseHas('contract_assets', ['serial_number' => 'SN-001']);
    }

    public function test_assign_asset_returns409_when_duplicate_assignment(): void
    {
        ContractAssetModel::create([
            'id' => 'ca000000-0000-0000-0000-000000000001',
            'contract_id' => $this->contract->id,
            'asset_id' => $this->asset->id,
            'serial_number' => 'SN-EXISTING',
        ]);

        $response = $this->postJson("/api/v1/contracts/{$this->contract->id}/assets", [
            'asset_id' => $this->asset->id,
            'serial_number' => 'SN-NEW',
        ]);

        $response->assertStatus(409)->assertJsonPath('error.code', 'ASSET_ALREADY_ASSIGNED');
    }

    public function test_assign_asset_returns409_when_serial_number_taken(): void
    {
        $asset2 = AssetModel::create([
            'id' => 'a1b2c3d4-0000-0000-0000-000000000002',
            'name' => 'Server Y',
            'manufacturer' => 'HP',
            'model' => 'ProLiant',
        ]);

        ContractAssetModel::create([
            'id' => 'ca000000-0000-0000-0000-000000000001',
            'contract_id' => $this->contract->id,
            'asset_id' => $asset2->id,
            'serial_number' => 'SN-TAKEN',
        ]);

        $response = $this->postJson("/api/v1/contracts/{$this->contract->id}/assets", [
            'asset_id' => $this->asset->id,
            'serial_number' => 'SN-TAKEN',
        ]);

        $response->assertStatus(409)->assertJsonPath('error.code', 'SERIAL_NUMBER_TAKEN');
    }

    public function test_assign_asset_returns422_when_asset_not_in_db(): void
    {
        $response = $this->postJson("/api/v1/contracts/{$this->contract->id}/assets", [
            'asset_id' => 'non-existent-uuid-0000-000000000000',
            'serial_number' => 'SN-001',
        ]);

        $response->assertUnprocessable();
    }

    public function test_remove_asset_returns204(): void
    {
        ContractAssetModel::create([
            'id' => 'ca000000-0000-0000-0000-000000000001',
            'contract_id' => $this->contract->id,
            'asset_id' => $this->asset->id,
            'serial_number' => 'SN-001',
        ]);

        $response = $this->deleteJson("/api/v1/contracts/{$this->contract->id}/assets/{$this->asset->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('contract_assets', ['serial_number' => 'SN-001']);
    }
}
