<?php declare(strict_types=1);

namespace App\Business\AssetRegistry\Contracts;

interface IUuidGenerator
{
    public function generate(): string;
}
