<?php

declare(strict_types=1);

namespace Vineethkrishnan\XrechnungKitShopware;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Vineethkrishnan\XrechnungKitShopware\Service\CustomFieldSetInstaller;

/**
 * Shopware 6 plugin entry point. The Shopware platform discovers this class
 * via composer.json's extra.shopware-plugin-class and loads
 * Resources/config/services.xml automatically.
 *
 * The lifecycle hooks below register and remove the xrechnung_kit custom
 * field set on the customer and order entities, and clean up the plugin's
 * own DAL table only when the operator opts in to data removal at uninstall.
 */
class XrechnungKitShopware extends Plugin
{
    public function install(InstallContext $installContext): void
    {
        parent::install($installContext);

        $this->getCustomFieldSetInstaller()->install($installContext->getContext());
    }

    public function uninstall(UninstallContext $uninstallContext): void
    {
        parent::uninstall($uninstallContext);

        if ($uninstallContext->keepUserData()) {
            return;
        }

        $this->getCustomFieldSetInstaller()->uninstall($uninstallContext->getContext());
        $this->dropPluginTable();
    }

    private function getCustomFieldSetInstaller(): CustomFieldSetInstaller
    {
        $installer = $this->container->get(CustomFieldSetInstaller::class);
        if (!$installer instanceof CustomFieldSetInstaller) {
            throw new \RuntimeException(sprintf(
                'Expected %s in container, got %s',
                CustomFieldSetInstaller::class,
                $installer === null ? 'null' : get_debug_type($installer),
            ));
        }

        return $installer;
    }

    private function dropPluginTable(): void
    {
        $connection = $this->container->get(Connection::class);
        if (!$connection instanceof Connection) {
            return;
        }

        $connection->executeStatement('DROP TABLE IF EXISTS `xrechnung_kit_invoice`');
    }
}
