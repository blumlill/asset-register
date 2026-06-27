<?php declare(strict_types=1);

namespace App\Business\AssetRegistry\Domain\Exceptions;

use RuntimeException;

final class AssetAlreadyAssignedException extends RuntimeException
{
    public readonly string $errorCode;

    public function __construct(string $assetId, string $contractId)
    {
        $this->errorCode = 'ASSET_ALREADY_ASSIGNED';
        parent::__construct("Asset '{$assetId}' is already assigned to contract '{$contractId}'.");
    }
}
