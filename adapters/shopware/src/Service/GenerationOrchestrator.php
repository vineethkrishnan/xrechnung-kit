<?php

declare(strict_types=1);

namespace Vineethkrishnan\XrechnungKitShopware\Service;

use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Uuid\Uuid;
use Vineethkrishnan\XrechnungKitShopware\Core\Content\XrechnungInvoice\XrechnungInvoiceDefinition;
use Vineethkrishnan\XrechnungKitShopware\Core\Content\XrechnungInvoice\XrechnungInvoiceEntity;
use Vineethkrishnan\XrechnungKitShopware\Notification\AdminAlerter;
use Vineethkrishnan\XrechnungKitShopware\Peppol\PeppolDeliveryService;
use XrechnungKit\Mapping\MappingData;

/**
 * Single entry point for "generate the XRechnung for this order".
 *
 * Used by:
 *  - OrderStateChangedSubscriber (auto-trigger on completed)
 *  - XrechnungController::regenerate (manual button in admin)
 *  - RetryFailedGenerationsTaskHandler (scheduled retry)
 *
 * Responsibilities:
 *  1. Load the order with the right associations
 *  2. Call OrderToMappingData
 *  3. Build the absolute target path under the configured output dir
 *  4. Make sure the output directory exists
 *  5. Call XrechnungService::generate
 *  6. Upsert the xrechnung_kit_invoice row with audit fields
 *
 * Errors are caught and persisted as a failed row; the caller decides
 * whether to surface them further (the subscriber does not, the
 * regenerate endpoint does, the retry task increments attempt_count).
 */
final class GenerationOrchestrator
{
    public function __construct(
        private readonly OrderToMappingData $mapper,
        private readonly XrechnungService $service,
        private readonly EntityRepository $orderRepository,
        private readonly EntityRepository $xrechnungInvoiceRepository,
        private readonly PluginConfig $config,
        private readonly LoggerInterface $logger,
        private readonly AdminAlerter $alerter,
        private readonly PeppolDeliveryService $peppolDelivery,
        private readonly string $projectDir,
    ) {
    }

    /**
     * Generates an XRechnung for the given order, persists the result,
     * and returns the entity id of the upserted xrechnung_kit_invoice row.
     */
    public function generateForOrder(
        string $orderId,
        Context $context,
        string $triggeredVia = XrechnungInvoiceDefinition::TRIGGER_ORDER_STATE,
        ?string $triggeredBy = null,
    ): string {
        $existing = $this->findExistingInvoice($orderId, $context);
        $invoiceId = $existing?->getId() ?? Uuid::randomHex();
        $attemptCount = ($existing?->getAttemptCount() ?? 0) + 1;

        try {
            $order = $this->loadOrder($orderId, $context);
        } catch (\Throwable $e) {
            $this->logger->error('xrechnung-kit-shopware: order load failed', [
                'orderId' => $orderId,
                'exception' => $e::class,
                'message' => $e->getMessage(),
            ]);
            $this->persistFailureRow(
                $invoiceId,
                $orderId,
                Defaults::LIVE_VERSION,
                $e,
                $triggeredVia,
                $triggeredBy,
                $attemptCount,
                null,
                $context,
            );
            $this->alertIfQuarantine($invoiceId, $context, null);
            return $invoiceId;
        }

        $salesChannelId = $order->getSalesChannelId();

        try {
            $mapping = $this->mapper->fromOrder($order);
            $targetPath = $this->computeTargetPath($order, $salesChannelId);
            $this->ensureDirectory(dirname($targetPath));

            $result = $this->service->generate(
                $mapping,
                $targetPath,
                $this->config->isKositEnabled($salesChannelId),
            );

            $this->xrechnungInvoiceRepository->upsert([
                array_merge(
                    [
                        'id' => $invoiceId,
                        'orderId' => $order->getId(),
                        'orderVersionId' => $order->getVersionId() ?? Defaults::LIVE_VERSION,
                        'status' => $result->status,
                        'generatedPath' => $result->path,
                        'errors' => $result->errors === [] ? null : $result->errors,
                        'generatedAt' => $result->generatedAt,
                        'mappingSnapshot' => $this->snapshotMapping($mapping),
                        'validatorVersion' => $result->validatorVersion,
                        'kositResult' => $result->kositResult,
                        'triggeredVia' => $triggeredVia,
                        'triggeredBy' => $triggeredBy,
                        'attemptCount' => $attemptCount,
                    ],
                    $existing === null ? ['deliveryStatus' => XrechnungInvoiceDefinition::DELIVERY_PENDING] : [],
                ),
            ], $context);

            $this->alertIfQuarantine($invoiceId, $context, $salesChannelId);
            $this->autoDeliverIfEnabled($invoiceId, $context, $salesChannelId);
        } catch (\Throwable $e) {
            $this->logger->error('xrechnung-kit-shopware: generation failed', [
                'orderId' => $orderId,
                'exception' => $e::class,
                'message' => $e->getMessage(),
            ]);
            $this->persistFailureRow(
                $invoiceId,
                $order->getId(),
                $order->getVersionId() ?? Defaults::LIVE_VERSION,
                $e,
                $triggeredVia,
                $triggeredBy,
                $attemptCount,
                null,
                $context,
            );
            $this->alertIfQuarantine($invoiceId, $context, $salesChannelId);
        }

        return $invoiceId;
    }

    private function alertIfQuarantine(string $invoiceId, Context $context, ?string $salesChannelId): void
    {
        $invoice = $this->xrechnungInvoiceRepository->search(new Criteria([$invoiceId]), $context)->first();
        if (!$invoice instanceof XrechnungInvoiceEntity) {
            return;
        }
        $this->alerter->notifyInvalid($invoice, $salesChannelId);
    }

    private function autoDeliverIfEnabled(string $invoiceId, Context $context, ?string $salesChannelId): void
    {
        if (!$this->config->isPeppolAutoDeliverEnabled($salesChannelId)) {
            return;
        }
        try {
            $this->peppolDelivery->deliver($invoiceId, $context);
        } catch (\Throwable $e) {
            $this->logger->warning('xrechnung-kit-shopware: peppol auto-deliver swallowed exception', [
                'invoiceId' => $invoiceId,
                'exception' => $e::class,
                'message' => $e->getMessage(),
            ]);
        }
    }

    private function loadOrder(string $orderId, Context $context): OrderEntity
    {
        $criteria = new Criteria([$orderId]);
        $criteria->addAssociation('lineItems');
        $criteria->addAssociation('billingAddress.country');
        $criteria->addAssociation('orderCustomer.customer');
        $criteria->addAssociation('currency');
        $criteria->addAssociation('salesChannel');

        $order = $this->orderRepository->search($criteria, $context)->first();
        if (!$order instanceof OrderEntity) {
            throw new \DomainException(sprintf('Order %s could not be loaded.', $orderId));
        }
        return $order;
    }

    private function findExistingInvoice(string $orderId, Context $context): ?XrechnungInvoiceEntity
    {
        $criteria = (new Criteria())->addFilter(new EqualsFilter('orderId', $orderId))->setLimit(1);
        $existing = $this->xrechnungInvoiceRepository->search($criteria, $context)->first();
        return $existing instanceof XrechnungInvoiceEntity ? $existing : null;
    }

    private function computeTargetPath(OrderEntity $order, ?string $salesChannelId): string
    {
        $relative = $this->config->getOutputDirectory($salesChannelId);
        $base = rtrim($this->projectDir, '/') . '/' . trim($relative, '/');
        $filename = sprintf('%s.xml', $order->getOrderNumber() ?? $order->getId());
        return $base . '/' . $filename;
    }

    private function ensureDirectory(string $dir): void
    {
        if (is_dir($dir)) {
            return;
        }
        if (!mkdir($dir, 0o755, true) && !is_dir($dir)) {
            throw new \RuntimeException(sprintf('Could not create output directory: %s', $dir));
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function snapshotMapping(MappingData $mapping): array
    {
        $buyer = $mapping->buyer;
        return [
            'invoiceNumber' => $mapping->meta->invoiceNumber,
            'issueDate' => $mapping->meta->issueDate->format('Y-m-d'),
            'currency' => $mapping->meta->currency,
            'sellerName' => $mapping->seller->name,
            'buyerName' => $buyer->name,
            'buyerLeitwegId' => $buyer->leitwegId,
            'lineCount' => count($mapping->lines),
            'totalsPayable' => $mapping->totals->payable->amount,
            'totalsCurrency' => $mapping->totals->payable->currency,
        ];
    }

    private function persistFailureRow(
        string $invoiceId,
        string $orderId,
        string $orderVersionId,
        \Throwable $error,
        string $triggeredVia,
        ?string $triggeredBy,
        int $attemptCount,
        ?array $partialSnapshot,
        Context $context,
    ): void {
        $existing = $this->xrechnungInvoiceRepository->search(new Criteria([$invoiceId]), $context)->first();

        $this->xrechnungInvoiceRepository->upsert([
            array_merge(
                [
                    'id' => $invoiceId,
                    'orderId' => $orderId,
                    'orderVersionId' => $orderVersionId,
                    'status' => XrechnungInvoiceDefinition::STATUS_FAILED,
                    'generatedPath' => null,
                    'errors' => [$error::class . ': ' . $error->getMessage()],
                    'generatedAt' => new \DateTimeImmutable(),
                    'mappingSnapshot' => $partialSnapshot,
                    'validatorVersion' => null,
                    'kositResult' => XrechnungInvoiceDefinition::KOSIT_SKIPPED,
                    'triggeredVia' => $triggeredVia,
                    'triggeredBy' => $triggeredBy,
                    'attemptCount' => $attemptCount,
                ],
                $existing === null ? ['deliveryStatus' => XrechnungInvoiceDefinition::DELIVERY_PENDING] : [],
            ),
        ], $context);
    }
}
