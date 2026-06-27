<?php declare(strict_types=1);

namespace App\Business\AssetRegistry\Domain\Exceptions;

use RuntimeException;

final class AssetHasActiveAssignmentsException extends RuntimeException
{
    public readonly string $errorCode;

    public function __construct(string $assetId)
    {
        $this->errorCode = 'ASSET_HAS_ACTIVE_ASSIGNMENTS';
        parent::__construct("Asset '{$assetId}' has active contract assignments and cannot be deleted.");
    }
}
