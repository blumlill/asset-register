<?php declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Repository\Eloquent\Models\AssetModel;
use App\Repository\Eloquent\Models\ContractAssetModel;
use App\Repository\Eloquent\Models\ContractModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssetApiTest extends TestCase
{
    use RefreshDatabase;

    public function testListAssetsReturnsEmptyArray(): void
    {
        $response = $this->getJson('/api/v1/assets');

        $response->assertOk()->assertJsonStructure(['data']);
        $this->assertEmpty($response->json('data'));
    }

    public function testCreateAssetReturns201(): void
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

    public function testCreateAssetValidationFailsWithMissingFields(): void
    {
        $response = $this->postJson('/api/v1/assets', []);

        $response->assertUnprocessable()
            ->assertJsonPath('error.code', 'VALIDATION_ERROR')
            ->assertJsonStructure(['error' => ['code', 'message', 'details']]);
    }

    public function testShowAssetReturns200(): void
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

    public function testShowAssetReturns404WhenNotFound(): void
    {
        $response = $this->getJson('/api/v1/assets/non-existent-uuid');

        $response->assertNotFound()
            ->assertJsonPath('error.code', 'ASSET_NOT_FOUND');
    }

    public function testUpdateAssetReturns200(): void
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

    public function testDeleteAssetReturns204(): void
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

    public function testDeleteAssetReturns409WhenHasActiveAssignments(): void
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
