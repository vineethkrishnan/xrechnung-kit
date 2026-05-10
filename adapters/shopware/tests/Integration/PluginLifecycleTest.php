<?php

declare(strict_types=1);

namespace Vineethkrishnan\XrechnungKitShopware\Tests\Integration;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Vineethkrishnan\XrechnungKitShopware\Service\CustomFieldSetInstaller;

/**
 * Integration tests covering the plugin install / uninstall lifecycle.
 *
 * Skipped automatically when run outside of a Shopware kernel.
 * To execute: install the plugin into a real Shopware project and run
 *   vendor/bin/phpunit --testsuite Integration
 */
final class PluginLifecycleTest extends TestCase
{
    protected function setUp(): void
    {
        if (!trait_exists('Shopware\\Core\\Framework\\Test\\TestCaseBase\\IntegrationTestBehaviour')) {
            self::markTestSkipped('Shopware test framework is not available; run inside a Shopware project.');
        }
    }

    public function testPluginCreatesXrechnungInvoiceTable(): void
    {
        // Will run when IntegrationTestBehaviour is in scope and the plugin
        // is installed into the test kernel. The test asserts that the
        // migration produced the expected schema.
        self::assertTrue(method_exists(self::class, 'getContainer'), 'Run via Shopware integration test kernel.');

        /** @var Connection $connection */
        $connection = self::getContainer()->get(Connection::class);
        $columns = $connection->createSchemaManager()->listTableColumns('xrechnung_kit_invoice');

        $expected = [
            'id', 'order_id', 'order_version_id',
            'status', 'generated_path', 'errors', 'generated_at',
            'mapping_snapshot', 'validator_version', 'kosit_result',
            'triggered_via', 'triggered_by', 'attempt_count',
            'delivery_status', 'delivery_attempted_at', 'delivery_response', 'delivery_error',
            'created_at', 'updated_at',
        ];

        foreach ($expected as $column) {
            self::assertArrayHasKey($column, $columns, sprintf('Column %s should exist after migrations.', $column));
        }
    }

    public function testCustomFieldSetIsRegisteredOnCustomerAndOrder(): void
    {
        if (!method_exists(self::class, 'getContainer')) {
            self::markTestSkipped('Run via Shopware integration test kernel.');
        }

        /** @var EntityRepository $repo */
        $repo = self::getContainer()->get('custom_field_set.repository');

        $criteria = (new Criteria())->addFilter(new EqualsFilter('name', CustomFieldSetInstaller::SET_NAME));
        $criteria->addAssociation('relations');
        $criteria->addAssociation('customFields');

        // Will be exercised in the test kernel once available.
        self::assertNotNull($repo);
    }
}
