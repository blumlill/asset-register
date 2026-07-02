<?php

declare(strict_types=1);

namespace App\Business\AssetRegistry\Domain\Exceptions;

final class AssetAlreadyAssignedException extends DomainException
{
    public function __construct(string $assetId, string $contractId)
    {
        parent::__construct(DomainErrorType::CONFLICT, 'ASSET_ALREADY_ASSIGNED', "Asset '{$assetId}' is already assigned to contract '{$contractId}'.");
    }
}
