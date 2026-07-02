<?php

declare(strict_types=1);

namespace App\Business\AssetRegistry\Domain\Exceptions;

final class AssetHasActiveAssignmentsException extends DomainException
{
    public function __construct(string $assetId)
    {
        parent::__construct(DomainErrorType::CONFLICT, 'ASSET_HAS_ACTIVE_ASSIGNMENTS', "Asset '{$assetId}' has active contract assignments and cannot be deleted.");
    }
}
