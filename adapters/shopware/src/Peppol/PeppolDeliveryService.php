<?php

declare(strict_types=1);

namespace Vineethkrishnan\XrechnungKitShopware\Peppol;

use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Order\Aggregate\OrderAddress\OrderAddressEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Uuid\Uuid;
use Vineethkrishnan\XrechnungKitShopware\Core\Content\XrechnungInvoice\XrechnungInvoiceDefinition;
use Vineethkrishnan\XrechnungKitShopware\Core\Content\XrechnungInvoice\XrechnungInvoiceEntity;
use Vineethkrishnan\XrechnungKitShopware\Service\PluginConfig;

/**
 * Orchestrates PEPPOL delivery of a generated XRechnung.
 *
 * Picks the active PeppolDeliveryInterface implementation, builds the
 * envelope from order + plugin config + buyer custom fields, runs the
 * delivery, and persists the result into the xrechnung_kit_invoice
 * row.
 *
 * Only generated invoices are delivered: invalid or failed XRechnungen
 * are quarantined, never sent. Recipients without a PEPPOL endpoint id
 * are skipped (delivery_status = skipped, delivery_error explains).
 */
final class PeppolDeliveryService
{
    /**
     * @param iterable<PeppolDeliveryInterface> $providers
     */
    public function __construct(
        private readonly iterable $providers,
        private readonly EntityRepository $xrechnungInvoiceRepository,
        private readonly EntityRepository $orderRepository,
        private readonly PluginConfig $config,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * Returns true when delivery was attempted (regardless of outcome),
     * false when the precondition gate skipped it (config disabled,
     * invoice not eligible, no recipient PEPPOL id resolvable).
     */
    public function deliver(string $invoiceId, Context $context): bool
    {
        $invoice = $this->loadInvoice($invoiceId, $context);
        if ($invoice === null) {
            $this->logger->info('xrechnung-kit-shopware: peppol skip, invoice missing', [
                'invoiceId' => $invoiceId,
            ]);
            return false;
        }

        if ($invoice->getStatus() !== XrechnungInvoiceDefinition::STATUS_GENERATED) {
            $this->persistResult(
                $invoice,
                PeppolDeliveryResult::skipped('Invoice is not in generated status; will not deliver.'),
                $context,
            );
            return false;
        }

        $order = $this->loadOrder($invoice->getOrderId(), $context);
        $salesChannelId = $order?->getSalesChannelId();

        if (!$this->config->isPeppolEnabled($salesChannelId)) {
            $this->persistResult(
                $invoice,
                PeppolDeliveryResult::skipped('PEPPOL delivery is disabled in plugin config.'),
                $context,
            );
            return false;
        }

        $envelope = $this->buildEnvelope($invoice, $order, $salesChannelId);
        if ($envelope === null) {
            $this->persistResult(
                $invoice,
                PeppolDeliveryResult::skipped('Recipient PEPPOL endpoint id is not set; cannot route.'),
                $context,
            );
            return false;
        }

        $provider = $this->pickProvider($salesChannelId);
        if ($provider === null) {
            $this->persistResult(
                $invoice,
                PeppolDeliveryResult::skipped('No configured PEPPOL delivery provider.'),
                $context,
            );
            return false;
        }

        $xmlPath = $invoice->getGeneratedPath();
        if ($xmlPath === null || $xmlPath === '' || !is_file($xmlPath)) {
            $this->persistResult(
                $invoice,
                PeppolDeliveryResult::failed('Generated XML file is missing on disk.'),
                $context,
            );
            return true;
        }

        $result = $provider->deliver($envelope, $xmlPath, $salesChannelId);
        $this->persistResult($invoice, $result, $context);
        return true;
    }

    private function pickProvider(?string $salesChannelId): ?PeppolDeliveryInterface
    {
        foreach ($this->providers as $provider) {
            if ($provider->isConfigured($salesChannelId)) {
                return $provider;
            }
        }
        return null;
    }

    private function buildEnvelope(
        XrechnungInvoiceEntity $invoice,
        ?OrderEntity $order,
        ?string $salesChannelId,
    ): ?PeppolEnvelope {
        $sender = $this->config->getPeppolSenderId($salesChannelId);
        if ($sender === null || $sender === '') {
            return null;
        }

        $recipient = $this->resolveRecipientId($order);
        if ($recipient === null || $recipient === '') {
            return null;
        }

        return new PeppolEnvelope(
            senderId: $sender,
            recipientId: $recipient,
            transmissionId: sprintf('xk-%s-%s', $invoice->getId(), Uuid::randomHex()),
        );
    }

    private function resolveRecipientId(?OrderEntity $order): ?string
    {
        if ($order === null) {
            return null;
        }

        $custom = $order->getCustomFields() ?? [];
        $candidate = $custom['xrechnungKitPeppolId'] ?? null;
        if (is_string($candidate) && trim($candidate) !== '') {
            return trim($candidate);
        }

        $customer = $order->getOrderCustomer()?->getCustomer();
        $customerCustom = $customer?->getCustomFields() ?? [];
        $candidate = $customerCustom['xrechnungKitPeppolId'] ?? null;
        if (is_string($candidate) && trim($candidate) !== '') {
            return trim($candidate);
        }

        return null;
    }

    private function persistResult(
        XrechnungInvoiceEntity $invoice,
        PeppolDeliveryResult $result,
        Context $context,
    ): void {
        $this->xrechnungInvoiceRepository->update([
            [
                'id' => $invoice->getId(),
                'deliveryStatus' => $result->status,
                'deliveryAttemptedAt' => $result->attemptedAt,
                'deliveryResponse' => $result->response,
                'deliveryError' => $result->error,
            ],
        ], $context);
    }

    private function loadInvoice(string $invoiceId, Context $context): ?XrechnungInvoiceEntity
    {
        $entity = $this->xrechnungInvoiceRepository->search(new Criteria([$invoiceId]), $context)->first();
        return $entity instanceof XrechnungInvoiceEntity ? $entity : null;
    }

    private function loadOrder(string $orderId, Context $context): ?OrderEntity
    {
        $criteria = new Criteria([$orderId]);
        $criteria->addAssociation('billingAddress.country');
        $criteria->addAssociation('orderCustomer.customer');

        $order = $this->orderRepository->search($criteria, $context)->first();
        return $order instanceof OrderEntity ? $order : null;
    }
}
