<?php

declare(strict_types=1);

namespace App\Business\AssetRegistry\Domain\Exceptions;

final class AssetNotFoundException extends DomainException
{
    public function __construct(string $assetId)
    {
        parent::__construct(DomainErrorType::NOT_FOUND, 'ASSET_NOT_FOUND', "Asset '{$assetId}' not found.");
    }
}
