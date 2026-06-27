<?php declare(strict_types=1);

namespace App\Business\AssetRegistry\Domain\Entities;

use DateTimeImmutable;

final class Asset
{
    public function __construct(
        private readonly string $id,
        private string $name,
        private string $manufacturer,
        private string $model,
        private ?DateTimeImmutable $deletedAt = null,
    ) {}

    public function getId(): string
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getManufacturer(): string
    {
        return $this->manufacturer;
    }

    public function getModel(): string
    {
        return $this->model;
    }

    public function getDeletedAt(): ?DateTimeImmutable
    {
        return $this->deletedAt;
    }

    public function isDeleted(): bool
    {
        return $this->deletedAt !== null;
    }

    public function update(string $name, string $manufacturer, string $model): void
    {
        $this->name = $name;
        $this->manufacturer = $manufacturer;
        $this->model = $model;
    }

    public function softDelete(DateTimeImmutable $at): void
    {
        $this->deletedAt = $at;
    }
}
