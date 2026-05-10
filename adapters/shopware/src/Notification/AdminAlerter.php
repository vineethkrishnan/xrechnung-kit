<?php

declare(strict_types=1);

namespace Vineethkrishnan\XrechnungKitShopware\Notification;

use Vineethkrishnan\XrechnungKitShopware\Core\Content\XrechnungInvoice\XrechnungInvoiceDefinition;
use Vineethkrishnan\XrechnungKitShopware\Core\Content\XrechnungInvoice\XrechnungInvoiceEntity;
use Vineethkrishnan\XrechnungKitShopware\Service\PluginConfig;
use XrechnungKit\Notification\Notification;
use XrechnungKit\Notification\NotificationChannelInterface;
use XrechnungKit\Notification\Severity;

/**
 * Fan-out for operator notifications when an XRechnung lands in
 * quarantine. Holds an injected list of NotificationChannelInterface
 * implementations (collected via the
 * xrechnung_kit_shopware.notification_channel service tag) and
 * dispatches to each.
 *
 * The plugin config gates whether to alert at all, so operators who
 * route alerts elsewhere (Slack, Sentry) can disable the in-admin
 * notifications without removing the channel.
 */
final class AdminAlerter
{
    /**
     * @param iterable<NotificationChannelInterface> $channels
     */
    public function __construct(
        private readonly iterable $channels,
        private readonly PluginConfig $config,
    ) {
    }

    public function notifyInvalid(XrechnungInvoiceEntity $invoice, ?string $salesChannelId = null): void
    {
        if (!$this->config->isAdminNotificationEnabled($salesChannelId)) {
            return;
        }
        if (!$this->isQuarantine($invoice->getStatus())) {
            return;
        }

        $errorCount = is_array($invoice->getErrors()) ? count($invoice->getErrors()) : 0;

        $notification = new Notification(
            title: 'XRechnung quarantined',
            body: sprintf(
                'Order %s landed in status %s with %d error%s. See the order detail XRechnung tab.',
                $invoice->getOrderId(),
                $invoice->getStatus(),
                $errorCount,
                $errorCount === 1 ? '' : 's',
            ),
            severity: Severity::Warning,
            context: [
                'invoiceId' => $invoice->getId(),
                'orderId' => $invoice->getOrderId(),
                'status' => $invoice->getStatus(),
                'attemptCount' => $invoice->getAttemptCount(),
                'triggeredVia' => $invoice->getTriggeredVia(),
            ],
        );

        foreach ($this->channels as $channel) {
            if (!$channel instanceof NotificationChannelInterface) {
                continue;
            }
            $channel->send($notification);
        }
    }

    private function isQuarantine(string $status): bool
    {
        return $status === XrechnungInvoiceDefinition::STATUS_INVALID
            || $status === XrechnungInvoiceDefinition::STATUS_FAILED;
    }
}
