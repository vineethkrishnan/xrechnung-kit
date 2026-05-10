<?php

declare(strict_types=1);

namespace Vineethkrishnan\XrechnungKitShopware\Notification;

use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Notification\NotificationService;
use XrechnungKit\Notification\Notification;
use XrechnungKit\Notification\NotificationChannelInterface;
use XrechnungKit\Notification\Severity;

/**
 * NotificationChannel that writes core's structured notifications
 * into Shopware's admin notification center, scoped to admins with
 * the xrechnung_kit_invoice:read privilege so it does not leak to
 * orders-only operators.
 *
 * Per the NotificationChannelInterface contract, transient delivery
 * errors are swallowed: if the admin notification center is briefly
 * unavailable we still want the rest of the pipeline to keep working.
 */
final class ShopwareAdminChannel implements NotificationChannelInterface
{
    public function __construct(
        private readonly NotificationService $notificationService,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function send(Notification $notification): void
    {
        try {
            $this->notificationService->createNotification([
                'status' => $this->mapSeverity($notification->severity),
                'message' => sprintf('%s: %s', $notification->title, $notification->body),
                'adminOnly' => true,
                'requiredPrivileges' => ['xrechnung_kit_invoice:read'],
            ], Context::createDefaultContext());
        } catch (\Throwable $e) {
            $this->logger->info('xrechnung-kit-shopware: admin-channel delivery dropped', [
                'exception' => $e::class,
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function name(): string
    {
        return 'shopware-admin';
    }

    private function mapSeverity(Severity $severity): string
    {
        return match ($severity) {
            Severity::Critical, Severity::Error => 'error',
            Severity::Warning => 'warning',
            Severity::Info => 'info',
        };
    }
}
