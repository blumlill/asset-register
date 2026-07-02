<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Repository\Eloquent\Models\AssetModel;
use App\Repository\Eloquent\Models\ContractAssetModel;
use App\Repository\Eloquent\Models\ContractModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssetApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_list_assets_returns_empty_array(): void
    {
        $response = $this->getJson('/api/v1/assets');

        $response->assertOk()->assertJsonStructure(['data']);
        $this->assertEmpty($response->json('data'));
    }

    public function test_list_assets_returns_all_assets(): void
    {
        AssetModel::create([
            'id' => 'a1b2c3d4-e5f6-7890-abcd-ef1234567890',
            'name' => 'Server Alpha',
            'manufacturer' => 'Dell',
            'model' => 'PowerEdge R750',
        ]);
        AssetModel::create([
            'id' => 'a1b2c3d4-e5f6-7890-abcd-ef1234567891',
            'name' => 'Server Beta',
            'manufacturer' => 'HP',
            'model' => 'ProLiant DL380',
        ]);

        $response = $this->getJson('/api/v1/assets');

        $response->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonStructure(['data' => [['id', 'name', 'manufacturer', 'model']]]);
    }

    public function test_create_asset_returns201(): void
    {
        $response = $this->postJson('/api/v1/assets', [
            'name' => 'Server Alpha',
            'manufacturer' => 'Dell',
            'model' => 'PowerEdge R750',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'Server Alpha')
            ->assertJsonPath('data.manufacturer', 'Dell')
            ->assertJsonPath('data.model', 'PowerEdge R750')
            ->assertJsonStructure(['data' => ['id', 'name', 'manufacturer', 'model']]);

        $this->assertDatabaseHas('assets', ['name' => 'Server Alpha']);
    }

    public function test_create_asset_validation_fails_with_missing_fields(): void
    {
        $response = $this->postJson('/api/v1/assets', []);

        $response->assertUnprocessable()
            ->assertJsonPath('error.code', 'VALIDATION_ERROR')
            ->assertJsonStructure(['error' => ['code', 'message', 'details']]);
    }

    public function test_show_asset_returns200(): void
    {
        $asset = AssetModel::create([
            'id' => 'a1b2c3d4-e5f6-7890-abcd-ef1234567890',
            'name' => 'Server Beta',
            'manufacturer' => 'HP',
            'model' => 'ProLiant DL380',
        ]);

        $response = $this->getJson("/api/v1/assets/{$asset->id}");

        $response->assertOk()->assertJsonPath('data.name', 'Server Beta');
    }

    public function test_show_asset_returns404_when_not_found(): void
    {
        $response = $this->getJson('/api/v1/assets/non-existent-uuid');

        $response->assertNotFound()
            ->assertJsonPath('error.code', 'ASSET_NOT_FOUND');
    }

    public function test_update_asset_returns200(): void
    {
        $asset = AssetModel::create([
            'id' => 'a1b2c3d4-e5f6-7890-abcd-ef1234567890',
            'name' => 'Old Name',
            'manufacturer' => 'Dell',
            'model' => 'Old Model',
        ]);

        $response = $this->putJson("/api/v1/assets/{$asset->id}", [
            'name' => 'New Name',
            'manufacturer' => 'HP',
            'model' => 'New Model',
        ]);

        $response->assertOk()->assertJsonPath('data.name', 'New Name');
        $this->assertDatabaseHas('assets', ['id' => $asset->id, 'name' => 'New Name']);
    }

    public function test_delete_asset_returns204(): void
    {
        $asset = AssetModel::create([
            'id' => 'a1b2c3d4-e5f6-7890-abcd-ef1234567890',
            'name' => 'Server X',
            'manufacturer' => 'Dell',
            'model' => 'Model Y',
        ]);

        $response = $this->deleteJson("/api/v1/assets/{$asset->id}");

        $response->assertNoContent();
        $this->assertSoftDeleted('assets', ['id' => $asset->id]);
    }

    public function test_delete_asset_returns409_when_has_active_assignments(): void
    {
        $asset = AssetModel::create([
            'id' => 'a1b2c3d4-e5f6-7890-abcd-ef1234567890',
            'name' => 'Server X',
            'manufacturer' => 'Dell',
            'model' => 'Model Y',
        ]);
        $contract = ContractModel::create([
            'id' => 'c1d2e3f4-0000-0000-0000-000000000001',
            'contract_number' => 'C-001',
            'client_name' => 'Corp',
            'start_date' => '2026-01-01',
        ]);
        ContractAssetModel::create([
            'id' => 'ca000000-0000-0000-0000-000000000001',
            'contract_id' => $contract->id,
            'asset_id' => $asset->id,
            'serial_number' => 'SN-001',
        ]);

        $response = $this->deleteJson("/api/v1/assets/{$asset->id}");

        $response->assertStatus(409)
            ->assertJsonPath('error.code', 'ASSET_HAS_ACTIVE_ASSIGNMENTS');
    }
}
