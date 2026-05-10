<?php

declare(strict_types=1);

namespace Vineethkrishnan\XrechnungKitShopware\Subscriber;

use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\StateMachine\Event\StateMachineStateChangeEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Vineethkrishnan\XrechnungKitShopware\Core\Content\XrechnungInvoice\XrechnungInvoiceDefinition;
use Vineethkrishnan\XrechnungKitShopware\Core\Content\XrechnungInvoice\XrechnungInvoiceEntity;
use Vineethkrishnan\XrechnungKitShopware\Service\OrderToMappingData;
use Vineethkrishnan\XrechnungKitShopware\Service\PluginConfig;
use Vineethkrishnan\XrechnungKitShopware\Service\XrechnungService;

/**
 * Auto-generates an XRechnung when an order transitions into the
 * configured trigger state (default: completed). The transition is
 * never blocked - errors during mapping or generation are caught
 * here, persisted as a failed row in xrechnung_kit_invoice, and
 * picked up by Phase D's retry / quarantine flows.
 */
final class OrderStateChangedSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly OrderToMappingData $mapper,
        private readonly XrechnungService $service,
        private readonly EntityRepository $orderRepository,
        private readonly EntityRepository $xrechnungInvoiceRepository,
        private readonly PluginConfig $config,
        private readonly LoggerInterface $logger,
        private readonly string $projectDir,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'state_machine.order.state_changed' => 'onOrderStateChanged',
        ];
    }

    public function onOrderStateChanged(StateMachineStateChangeEvent $event): void
    {
        if ($event->getTransitionSide() !== StateMachineStateChangeEvent::STATE_MACHINE_TRANSITION_SIDE_ENTER) {
            return;
        }

        $context = $event->getContext();
        $newState = $event->getStateName();
        $orderId = $event->getTransition()->getEntityId();

        $salesChannelId = $this->resolveSalesChannelId($orderId, $context);

        if (!$this->config->isAutoGenerationEnabled($salesChannelId)) {
            return;
        }
        if ($newState !== $this->config->getAutoGenerateOnState($salesChannelId)) {
            return;
        }

        try {
            $order = $this->loadOrder($orderId, $context);
            $mapping = $this->mapper->fromOrder($order);
            $targetPath = $this->computeTargetPath($order, $salesChannelId);
            $this->ensureDirectory(dirname($targetPath));

            $result = $this->service->generate(
                $mapping,
                $targetPath,
                $this->config->isKositEnabled($salesChannelId),
            );

            $this->persistResult($order, $result, $context);
        } catch (\Throwable $e) {
            $this->logger->error('xrechnung-kit-shopware: generation failed', [
                'orderId' => $orderId,
                'state' => $newState,
                'exception' => $e::class,
                'message' => $e->getMessage(),
            ]);
            $this->persistFailure($orderId, $e, $context);
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

    private function resolveSalesChannelId(string $orderId, Context $context): ?string
    {
        $criteria = new Criteria([$orderId]);
        $order = $this->orderRepository->search($criteria, $context)->first();
        return $order instanceof OrderEntity ? $order->getSalesChannelId() : null;
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

    private function persistResult(
        OrderEntity $order,
        \Vineethkrishnan\XrechnungKitShopware\Service\XrechnungGenerationResult $result,
        Context $context,
    ): void {
        $existingId = $this->findExistingInvoiceId($order->getId(), $context);

        $this->xrechnungInvoiceRepository->upsert([
            [
                'id' => $existingId ?? Uuid::randomHex(),
                'orderId' => $order->getId(),
                'orderVersionId' => $order->getVersionId() ?? Defaults::LIVE_VERSION,
                'status' => $result->status,
                'generatedPath' => $result->path,
                'errors' => $result->errors === [] ? null : $result->errors,
                'generatedAt' => $result->generatedAt,
                'mappingSnapshot' => null,
                'validatorVersion' => $result->validatorVersion,
                'kositResult' => $result->kositResult,
            ],
        ], $context);
    }

    private function persistFailure(string $orderId, \Throwable $error, Context $context): void
    {
        $existingId = $this->findExistingInvoiceId($orderId, $context);

        $this->xrechnungInvoiceRepository->upsert([
            [
                'id' => $existingId ?? Uuid::randomHex(),
                'orderId' => $orderId,
                'orderVersionId' => Defaults::LIVE_VERSION,
                'status' => XrechnungInvoiceDefinition::STATUS_FAILED,
                'generatedPath' => null,
                'errors' => [$error::class . ': ' . $error->getMessage()],
                'generatedAt' => new \DateTimeImmutable(),
                'mappingSnapshot' => null,
                'validatorVersion' => null,
                'kositResult' => XrechnungInvoiceDefinition::KOSIT_SKIPPED,
            ],
        ], $context);
    }

    private function findExistingInvoiceId(string $orderId, Context $context): ?string
    {
        $criteria = (new Criteria())->addFilter(new EqualsFilter('orderId', $orderId))->setLimit(1);
        $existing = $this->xrechnungInvoiceRepository->search($criteria, $context)->first();
        return $existing instanceof XrechnungInvoiceEntity ? $existing->getId() : null;
    }
}
