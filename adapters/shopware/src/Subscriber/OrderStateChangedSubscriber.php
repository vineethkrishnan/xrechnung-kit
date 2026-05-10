<?php

declare(strict_types=1);

namespace Vineethkrishnan\XrechnungKitShopware\Subscriber;

use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\StateMachine\Event\StateMachineStateChangeEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Vineethkrishnan\XrechnungKitShopware\Core\Content\XrechnungInvoice\XrechnungInvoiceDefinition;
use Vineethkrishnan\XrechnungKitShopware\Service\GenerationOrchestrator;
use Vineethkrishnan\XrechnungKitShopware\Service\PluginConfig;

/**
 * Listens for order state transitions and delegates the actual
 * generation to GenerationOrchestrator. Stays tiny on purpose: the
 * shared logic for "generate and persist" lives in the orchestrator
 * so this subscriber, the manual regenerate endpoint, and the
 * scheduled retry task all behave identically.
 */
final class OrderStateChangedSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly GenerationOrchestrator $orchestrator,
        private readonly EntityRepository $orderRepository,
        private readonly PluginConfig $config,
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

        $orderId = $event->getTransition()->getEntityId();
        $context = $event->getContext();
        $newState = $event->getStateName();

        // Cheap fetch (no associations) just to read the sales channel id
        // for the per-channel config lookup. Skips early if the order is gone.
        $simpleOrder = $this->orderRepository->search(new Criteria([$orderId]), $context)->first();
        if (!$simpleOrder instanceof OrderEntity) {
            return;
        }
        $salesChannelId = $simpleOrder->getSalesChannelId();

        if (!$this->config->isAutoGenerationEnabled($salesChannelId)) {
            return;
        }
        if ($newState !== $this->config->getAutoGenerateOnState($salesChannelId)) {
            return;
        }

        $this->orchestrator->generateForOrder(
            $orderId,
            $context,
            XrechnungInvoiceDefinition::TRIGGER_ORDER_STATE,
            null,
        );
    }
}
