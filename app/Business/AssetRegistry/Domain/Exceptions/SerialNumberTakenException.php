<?php declare(strict_types=1);

namespace App\Business\AssetRegistry\Domain\Exceptions;

use RuntimeException;

final class SerialNumberTakenException extends RuntimeException
{
    public readonly string $errorCode;

    public function __construct(string $serialNumber)
    {
        $this->errorCode = 'SERIAL_NUMBER_TAKEN';
        parent::__construct("Serial number '{$serialNumber}' is already in use.");
    }
}
