<?php

declare(strict_types=1);

namespace App\Business\AssetRegistry\Domain\Exceptions;

enum DomainErrorType
{
    case NOT_FOUND;
    case CONFLICT;
}
