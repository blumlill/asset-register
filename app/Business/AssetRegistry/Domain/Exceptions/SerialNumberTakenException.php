<?php

declare(strict_types=1);

namespace App\Business\AssetRegistry\Domain\Exceptions;

final class SerialNumberTakenException extends DomainException
{
    public function __construct(string $serialNumber)
    {
        parent::__construct(DomainErrorType::CONFLICT, 'SERIAL_NUMBER_TAKEN', "Serial number '{$serialNumber}' is already in use.");
    }
}
