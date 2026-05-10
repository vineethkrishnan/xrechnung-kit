<?php

declare(strict_types=1);

namespace Vineethkrishnan\XrechnungKitShopware\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

/**
 * Adds audit columns to xrechnung_kit_invoice for Phase D:
 *
 *  triggered_via   one of order_state, manual, scheduled_retry, api
 *  triggered_by    Shopware user UUID for manual triggers, null otherwise
 *  attempt_count   number of generation attempts for the current order;
 *                  the scheduled retry handler increments and uses this
 *                  to enforce a max-retries gate
 */
class Migration1731000000XrechnungInvoiceAudit extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1731000000;
    }

    public function update(Connection $connection): void
    {
        $sql = <<<'SQL'
            ALTER TABLE `xrechnung_kit_invoice`
                ADD COLUMN `triggered_via` VARCHAR(32) NOT NULL DEFAULT 'order_state' AFTER `kosit_result`,
                ADD COLUMN `triggered_by` VARCHAR(36) DEFAULT NULL AFTER `triggered_via`,
                ADD COLUMN `attempt_count` INT UNSIGNED NOT NULL DEFAULT 1 AFTER `triggered_by`,
                ADD INDEX `idx.xrechnung_kit_invoice.triggered_via` (`triggered_via`);
        SQL;

        $connection->executeStatement($sql);
    }

    public function updateDestructive(Connection $connection): void
    {
        // No destructive changes needed.
    }
}
