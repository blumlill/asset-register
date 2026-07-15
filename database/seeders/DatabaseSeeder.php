<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Repository\Eloquent\Models\AssetModel;
use App\Repository\Eloquent\Models\ContractAssetModel;
use App\Repository\Eloquent\Models\ContractModel;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Assets
        $server = AssetModel::create([
            'name' => 'Dell PowerEdge R750',
            'manufacturer' => 'Dell',
            'model' => 'PowerEdge R750',
        ]);

        $laptop = AssetModel::create([
            'name' => 'ThinkPad X1 Carbon',
            'manufacturer' => 'Lenovo',
            'model' => 'X1 Carbon Gen 11',
        ]);

        $switch = AssetModel::create([
            'name' => 'Catalyst 9300',
            'manufacturer' => 'Cisco',
            'model' => 'C9300-48P',
        ]);

        $unassigned = AssetModel::create([
            'name' => 'HP ProLiant DL360',
            'manufacturer' => 'HP',
            'model' => 'ProLiant DL360 Gen10',
        ]);

        // Contracts
        $contractA = ContractModel::create([
            'contract_number' => 'C-2026-001',
            'client_name' => 'Acme Corporation',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
        ]);

        $contractB = ContractModel::create([
            'contract_number' => 'C-2026-002',
            'client_name' => 'Globex Industries',
            'start_date' => '2026-03-01',
            'end_date' => null,
        ]);

        // Assignments
        ContractAssetModel::create([
            'contract_id' => $contractA->id,
            'asset_id' => $server->id,
            'serial_number' => 'SN-DELL-001',
        ]);

        ContractAssetModel::create([
            'contract_id' => $contractA->id,
            'asset_id' => $laptop->id,
            'serial_number' => 'SN-LNV-001',
        ]);

        ContractAssetModel::create([
            'contract_id' => $contractB->id,
            'asset_id' => $switch->id,
            'serial_number' => 'SN-CSC-001',
        ]);

        // $server also appears on contractB under a different serial number (same unit, second contract)
        ContractAssetModel::create([
            'contract_id' => $contractB->id,
            'asset_id' => $server->id,
            'serial_number' => 'SN-DELL-002',
        ]);
    }
}
