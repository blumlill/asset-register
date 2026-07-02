<?php

declare(strict_types=1);

namespace Tests\Unit\Business;

use App\Business\AssetRegistry\Domain\Aggregates\ContractAggregate;
use App\Business\AssetRegistry\Domain\Entities\Asset;
use App\Business\AssetRegistry\Domain\Entities\Contract;
use App\Business\AssetRegistry\Domain\Entities\ContractAsset;
use App\Business\AssetRegistry\Domain\Exceptions\AssetAlreadyAssignedException;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class ContractAggregateTest extends TestCase
{
    private Contract $contract;

    protected function setUp(): void
    {
        $this->contract = new Contract(
            'contract-uuid',
            'CONTRACT-001',
            'Acme Corp',
            new DateTimeImmutable('2026-01-01'),
        );
    }

    public function test_assign_asset_happy_path(): void
    {
        $aggregate = new ContractAggregate($this->contract);
        $contractAsset = new ContractAsset('ca-uuid', 'contract-uuid', 'asset-uuid', 'SN-001');

        $aggregate->assignAsset($contractAsset);

        $this->assertCount(1, $aggregate->getContractAssets());
        $this->assertSame($contractAsset, $aggregate->getContractAssets()[0]);
    }

    public function test_assign_duplicate_asset_throws(): void
    {
        $aggregate = new ContractAggregate($this->contract);
        $ca1 = new ContractAsset('ca-uuid-1', 'contract-uuid', 'asset-uuid', 'SN-001');
        $ca2 = new ContractAsset('ca-uuid-2', 'contract-uuid', 'asset-uuid', 'SN-002');

        $aggregate->assignAsset($ca1);

        $this->expectException(AssetAlreadyAssignedException::class);
        $aggregate->assignAsset($ca2);
    }

    public function test_remove_asset(): void
    {
        $ca = new ContractAsset('ca-uuid', 'contract-uuid', 'asset-uuid', 'SN-001');
        $aggregate = new ContractAggregate($this->contract, [$ca]);

        $aggregate->removeAsset('asset-uuid');

        $this->assertCount(0, $aggregate->getContractAssets());
    }

    public function test_constructor_loads_existing_assets(): void
    {
        $ca = new ContractAsset('ca-uuid', 'contract-uuid', 'asset-uuid', 'SN-001');
        $asset = new Asset('asset-uuid', 'Server X', 'Dell', 'PowerEdge');

        $aggregate = new ContractAggregate($this->contract, [$ca], [$asset]);

        $this->assertSame($asset, $aggregate->getAssetDetail('asset-uuid'));
    }

    public function test_assign_asset_error_code_is_correct(): void
    {
        $aggregate = new ContractAggregate($this->contract);
        $ca1 = new ContractAsset('ca-1', 'contract-uuid', 'asset-uuid', 'SN-001');
        $ca2 = new ContractAsset('ca-2', 'contract-uuid', 'asset-uuid', 'SN-002');
        $aggregate->assignAsset($ca1);

        try {
            $aggregate->assignAsset($ca2);
            $this->fail('Expected exception not thrown');
        } catch (AssetAlreadyAssignedException $e) {
            $this->assertSame('ASSET_ALREADY_ASSIGNED', $e->errorCode);
        }
    }
}
