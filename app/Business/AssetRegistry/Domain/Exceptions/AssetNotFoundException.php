<?php declare(strict_types=1);

namespace App\Business\AssetRegistry\Domain\Exceptions;

use RuntimeException;

final class AssetNotFoundException extends RuntimeException
{
    public readonly string $errorCode;

    public function __construct(string $assetId)
    {
        $this->errorCode = 'ASSET_NOT_FOUND';
        parent::__construct("Asset '{$assetId}' not found.");
    }
}
