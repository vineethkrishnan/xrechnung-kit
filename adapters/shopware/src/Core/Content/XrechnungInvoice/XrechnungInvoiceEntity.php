<?php

declare(strict_types=1);

namespace Vineethkrishnan\XrechnungKitShopware\Core\Content\XrechnungInvoice;

use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;

class XrechnungInvoiceEntity extends Entity
{
    use EntityIdTrait;

    protected string $orderId;

    protected string $orderVersionId;

    protected string $status;

    protected ?string $generatedPath = null;

    /** @var array<int, string>|null */
    protected ?array $errors = null;

    protected ?\DateTimeInterface $generatedAt = null;

    /** @var array<string, mixed>|null */
    protected ?array $mappingSnapshot = null;

    protected ?string $validatorVersion = null;

    protected ?string $kositResult = null;

    protected ?OrderEntity $order = null;

    public function getOrderId(): string
    {
        return $this->orderId;
    }

    public function setOrderId(string $orderId): void
    {
        $this->orderId = $orderId;
    }

    public function getOrderVersionId(): string
    {
        return $this->orderVersionId;
    }

    public function setOrderVersionId(string $orderVersionId): void
    {
        $this->orderVersionId = $orderVersionId;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    public function getGeneratedPath(): ?string
    {
        return $this->generatedPath;
    }

    public function setGeneratedPath(?string $generatedPath): void
    {
        $this->generatedPath = $generatedPath;
    }

    /** @return array<int, string>|null */
    public function getErrors(): ?array
    {
        return $this->errors;
    }

    /** @param array<int, string>|null $errors */
    public function setErrors(?array $errors): void
    {
        $this->errors = $errors;
    }

    public function getGeneratedAt(): ?\DateTimeInterface
    {
        return $this->generatedAt;
    }

    public function setGeneratedAt(?\DateTimeInterface $generatedAt): void
    {
        $this->generatedAt = $generatedAt;
    }

    /** @return array<string, mixed>|null */
    public function getMappingSnapshot(): ?array
    {
        return $this->mappingSnapshot;
    }

    /** @param array<string, mixed>|null $mappingSnapshot */
    public function setMappingSnapshot(?array $mappingSnapshot): void
    {
        $this->mappingSnapshot = $mappingSnapshot;
    }

    public function getValidatorVersion(): ?string
    {
        return $this->validatorVersion;
    }

    public function setValidatorVersion(?string $validatorVersion): void
    {
        $this->validatorVersion = $validatorVersion;
    }

    public function getKositResult(): ?string
    {
        return $this->kositResult;
    }

    public function setKositResult(?string $kositResult): void
    {
        $this->kositResult = $kositResult;
    }

    public function getOrder(): ?OrderEntity
    {
        return $this->order;
    }

    public function setOrder(?OrderEntity $order): void
    {
        $this->order = $order;
    }
}
