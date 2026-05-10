<?php

declare(strict_types=1);

namespace Vineethkrishnan\XrechnungKitShopware\ScheduledTask;

use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTask;

/**
 * Daily sweep that retries xrechnung_kit_invoice rows in status
 * "failed" or "invalid". Caps at MAX_ATTEMPTS to keep noisy losers
 * from cycling forever.
 *
 * The actual retry logic lives in RetryFailedGenerationsTaskHandler;
 * this class only declares the schedule.
 */
class RetryFailedGenerationsTask extends ScheduledTask
{
    /**
     * Hard cap on retries. After MAX_ATTEMPTS the row stays in its
     * final status and human attention is required from the
     * quarantine list view.
     */
    public const MAX_ATTEMPTS = 5;

    public static function getTaskName(): string
    {
        return 'xrechnung_kit.retry_failed_generations';
    }

    public static function getDefaultInterval(): int
    {
        // Once per day, expressed in seconds.
        return 24 * 60 * 60;
    }

    public static function shouldRescheduleOnFailure(): bool
    {
        return true;
    }
}
