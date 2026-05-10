<?php

declare(strict_types=1);

namespace Vineethkrishnan\XrechnungKitShopware\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1730000000XrechnungInvoice extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1730000000;
    }

    public function update(Connection $connection): void
    {
        $sql = <<<'SQL'
            CREATE TABLE IF NOT EXISTS `xrechnung_kit_invoice` (
                `id` BINARY(16) NOT NULL,
                `order_id` BINARY(16) NOT NULL,
                `order_version_id` BINARY(16) NOT NULL,
                `status` VARCHAR(32) NOT NULL,
                `generated_path` VARCHAR(2048) DEFAULT NULL,
                `errors` JSON DEFAULT NULL,
                `generated_at` DATETIME(3) DEFAULT NULL,
                `mapping_snapshot` JSON DEFAULT NULL,
                `validator_version` VARCHAR(64) DEFAULT NULL,
                `kosit_result` VARCHAR(32) DEFAULT NULL,
                `created_at` DATETIME(3) NOT NULL,
                `updated_at` DATETIME(3) DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `idx.xrechnung_kit_invoice.order_id` (`order_id`, `order_version_id`),
                KEY `idx.xrechnung_kit_invoice.status` (`status`),
                CONSTRAINT `json.xrechnung_kit_invoice.errors` CHECK (JSON_VALID(`errors`)),
                CONSTRAINT `json.xrechnung_kit_invoice.mapping_snapshot` CHECK (JSON_VALID(`mapping_snapshot`)),
                CONSTRAINT `fk.xrechnung_kit_invoice.order_id`
                    FOREIGN KEY (`order_id`, `order_version_id`)
                    REFERENCES `order` (`id`, `version_id`)
                    ON DELETE CASCADE
                    ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        SQL;

        $connection->executeStatement($sql);
    }

    public function updateDestructive(Connection $connection): void
    {
        // Destructive cleanup is opt-in via the plugin's keepUserData flag,
        // handled in XrechnungKitShopware::uninstall(). Nothing to do here.
    }
}
