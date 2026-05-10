<?php

declare(strict_types=1);

namespace Vineethkrishnan\XrechnungKitShopware\Tests\Service;

use PHPUnit\Framework\TestCase;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Vineethkrishnan\XrechnungKitShopware\Service\PluginConfig;

/**
 * Unit tests for PluginConfig. Mocks SystemConfigService directly and
 * exercises every getter so the contract between config keys and
 * defaults is covered in CI without spinning up a Shopware kernel.
 */
final class PluginConfigTest extends TestCase
{
    public function testReadsExplicitSellerName(): void
    {
        $configService = $this->createMock(SystemConfigService::class);
        $configService
            ->method('get')
            ->willReturnCallback(static function (string $key): mixed {
                return match ($key) {
                    'XrechnungKitShopware.config.sellerName' => 'Beispiel GmbH',
                    default => null,
                };
            });

        $config = new PluginConfig($configService);
        self::assertSame('Beispiel GmbH', $config->getSellerName());
    }

    public function testFallsBackToDefaultCountryCodeWhenUnset(): void
    {
        $configService = $this->createMock(SystemConfigService::class);
        $configService->method('get')->willReturn(null);

        $config = new PluginConfig($configService);
        self::assertSame('DE', $config->getSellerCountryCode());
    }

    public function testFallsBackToDefaultOutputDirectoryWhenUnset(): void
    {
        $configService = $this->createMock(SystemConfigService::class);
        $configService->method('get')->willReturn(null);

        $config = new PluginConfig($configService);
        self::assertSame('var/files/xrechnung', $config->getOutputDirectory());
    }

    public function testEmptyStringEndpointEmailIsTreatedAsNull(): void
    {
        $configService = $this->createMock(SystemConfigService::class);
        $configService
            ->method('get')
            ->willReturnCallback(static function (string $key): mixed {
                return match ($key) {
                    'XrechnungKitShopware.config.sellerEndpointEmail' => '',
                    default => null,
                };
            });

        $config = new PluginConfig($configService);
        self::assertNull($config->getSellerEndpointEmail());
    }

    public function testIsAutoGenerationEnabledTrueWhenStateIsCompleted(): void
    {
        $configService = $this->createMock(SystemConfigService::class);
        $configService
            ->method('get')
            ->willReturnCallback(static function (string $key): mixed {
                return match ($key) {
                    'XrechnungKitShopware.config.autoGenerateOnState' => 'completed',
                    default => null,
                };
            });

        $config = new PluginConfig($configService);
        self::assertTrue($config->isAutoGenerationEnabled());
        self::assertSame('completed', $config->getAutoGenerateOnState());
    }

    public function testIsAutoGenerationEnabledFalseWhenManual(): void
    {
        $configService = $this->createMock(SystemConfigService::class);
        $configService
            ->method('get')
            ->willReturnCallback(static function (string $key): mixed {
                return match ($key) {
                    'XrechnungKitShopware.config.autoGenerateOnState' => PluginConfig::TRIGGER_MANUAL,
                    default => null,
                };
            });

        $config = new PluginConfig($configService);
        self::assertFalse($config->isAutoGenerationEnabled());
    }

    public function testBoolGetterAcceptsStringTrueAndOne(): void
    {
        $configService = $this->createMock(SystemConfigService::class);
        $configService
            ->method('get')
            ->willReturnCallback(static function (string $key): mixed {
                return match ($key) {
                    'XrechnungKitShopware.config.kositEnabled' => '1',
                    'XrechnungKitShopware.config.notifyAdminOnInvalid' => 'true',
                    default => null,
                };
            });

        $config = new PluginConfig($configService);
        self::assertTrue($config->isKositEnabled());
        self::assertTrue($config->isAdminNotificationEnabled());
    }

    public function testPeppolGettersAreOffByDefault(): void
    {
        $configService = $this->createMock(SystemConfigService::class);
        $configService->method('get')->willReturn(null);

        $config = new PluginConfig($configService);
        self::assertFalse($config->isPeppolEnabled());
        self::assertFalse($config->isPeppolAutoDeliverEnabled());
        self::assertNull($config->getPeppolWebhookUrl());
        self::assertNull($config->getPeppolWebhookBearerToken());
        self::assertNull($config->getPeppolSenderId());
    }
}
