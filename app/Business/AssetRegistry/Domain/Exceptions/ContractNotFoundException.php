<?php declare(strict_types=1);

namespace App\Business\AssetRegistry\Domain\Exceptions;

use RuntimeException;

final class ContractNotFoundException extends RuntimeException
{
    public readonly string $errorCode;

    public function __construct(string $contractId)
    {
        $this->errorCode = 'CONTRACT_NOT_FOUND';
        parent::__construct("Contract '{$contractId}' not found.");
    }
}
