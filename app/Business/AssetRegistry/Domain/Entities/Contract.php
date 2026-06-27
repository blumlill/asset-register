<?php declare(strict_types=1);

namespace App\Business\AssetRegistry\Domain\Entities;

use DateTimeImmutable;

final class Contract
{
    public function __construct(
        private readonly string $id,
        private string $contractNumber,
        private string $clientName,
        private DateTimeImmutable $startDate,
        private ?DateTimeImmutable $endDate = null,
    ) {}

    public function getId(): string
    {
        return $this->id;
    }

    public function getContractNumber(): string
    {
        return $this->contractNumber;
    }

    public function getClientName(): string
    {
        return $this->clientName;
    }

    public function getStartDate(): DateTimeImmutable
    {
        return $this->startDate;
    }

    public function getEndDate(): ?DateTimeImmutable
    {
        return $this->endDate;
    }

    public function update(
        string $contractNumber,
        string $clientName,
        DateTimeImmutable $startDate,
        ?DateTimeImmutable $endDate,
    ): void {
        $this->contractNumber = $contractNumber;
        $this->clientName = $clientName;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }
}
