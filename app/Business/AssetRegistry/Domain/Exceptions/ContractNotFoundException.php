<?php

declare(strict_types=1);

namespace App\Business\AssetRegistry\Domain\Exceptions;

final class ContractNotFoundException extends DomainException
{
    public function __construct(string $contractId)
    {
        parent::__construct(DomainErrorType::NOT_FOUND, 'CONTRACT_NOT_FOUND', "Contract '{$contractId}' not found.");
    }
}
