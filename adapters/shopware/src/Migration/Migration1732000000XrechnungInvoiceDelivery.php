<?php

declare(strict_types=1);

namespace Vineethkrishnan\XrechnungKitShopware\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

/**
 * Adds PEPPOL delivery columns to xrechnung_kit_invoice for Phase 5:
 *
 *  delivery_status        one of pending, sent, failed, skipped
 *  delivery_attempted_at  last delivery attempt timestamp, null if
 *                         never attempted
 *  delivery_response      free-form JSON returned by the AP provider
 *                         (response body, transmission id, etc.)
 *  delivery_error         single-line error message from the most
 *                         recent failed attempt
 */
class Migration1732000000XrechnungInvoiceDelivery extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1732000000;
    }

    public function update(Connection $connection): void
    {
        $sql = <<<'SQL'
            ALTER TABLE `xrechnung_kit_invoice`
                ADD COLUMN `delivery_status` VARCHAR(32) NOT NULL DEFAULT 'pending' AFTER `attempt_count`,
                ADD COLUMN `delivery_attempted_at` DATETIME(3) DEFAULT NULL AFTER `delivery_status`,
                ADD COLUMN `delivery_response` JSON DEFAULT NULL AFTER `delivery_attempted_at`,
                ADD COLUMN `delivery_error` VARCHAR(2048) DEFAULT NULL AFTER `delivery_response`,
                ADD INDEX `idx.xrechnung_kit_invoice.delivery_status` (`delivery_status`),
                ADD CONSTRAINT `json.xrechnung_kit_invoice.delivery_response`
                    CHECK (JSON_VALID(`delivery_response`));
        SQL;

        $connection->executeStatement($sql);
    }

    public function updateDestructive(Connection $connection): void
    {
        // No destructive changes needed.
    }
}
