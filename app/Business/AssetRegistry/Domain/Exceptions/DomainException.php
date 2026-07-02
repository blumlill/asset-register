<?php

declare(strict_types=1);

namespace App\Business\AssetRegistry\Domain\Exceptions;

use RuntimeException;

abstract class DomainException extends RuntimeException
{
    public function __construct(
        public readonly DomainErrorType $errorType,
        public readonly string $errorCode,
        string $message,
    ) {
        parent::__construct($message);
    }
}
