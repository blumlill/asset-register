<?php

declare(strict_types=1);

namespace App\Business\AssetRegistry\Domain\Exceptions;

final class AssetNotAssignedException extends DomainException
{
    public function __construct(string $assetId, string $contractId)
    {
        parent::__construct(DomainErrorType::NOT_FOUND, 'ASSET_NOT_ASSIGNED', "Asset '{$assetId}' is not assigned to contract '{$contractId}'.");
    }
}
