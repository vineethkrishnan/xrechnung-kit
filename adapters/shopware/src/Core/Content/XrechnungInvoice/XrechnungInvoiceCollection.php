<?php

declare(strict_types=1);

namespace Vineethkrishnan\XrechnungKitShopware\Core\Content\XrechnungInvoice;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @extends EntityCollection<XrechnungInvoiceEntity>
 *
 * @method XrechnungInvoiceEntity[]      getIterator()
 * @method XrechnungInvoiceEntity[]      getElements()
 * @method XrechnungInvoiceEntity|null   get(string $key)
 * @method XrechnungInvoiceEntity|null   first()
 * @method XrechnungInvoiceEntity|null   last()
 */
class XrechnungInvoiceCollection extends EntityCollection
{
    public function getOrderIds(): array
    {
        return $this->fmap(static fn (XrechnungInvoiceEntity $invoice) => $invoice->getOrderId());
    }

    public function filterByOrderId(string $orderId): self
    {
        return $this->filter(static fn (XrechnungInvoiceEntity $invoice) => $invoice->getOrderId() === $orderId);
    }

    public function filterByStatus(string $status): self
    {
        return $this->filter(static fn (XrechnungInvoiceEntity $invoice) => $invoice->getStatus() === $status);
    }

    protected function getExpectedClass(): string
    {
        return XrechnungInvoiceEntity::class;
    }
}
