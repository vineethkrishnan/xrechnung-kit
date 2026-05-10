<?php

declare(strict_types=1);

namespace Vineethkrishnan\XrechnungKitShopware\Service;

use Shopware\Core\System\SystemConfig\SystemConfigService;

/**
 * Typed accessor over Shopware's SystemConfigService for this plugin's
 * configuration card. Keeps string keys in one place and gives the
 * mapper / subscriber a clean read surface.
 *
 * All getters accept an optional sales-channel id so per-sales-channel
 * overrides set in the admin work the same as global settings.
 */
final class PluginConfig
{
    private const NS = 'XrechnungKitShopware.config';

    public const LEITWEG_FROM_ORDER_FIELD = 'order_custom_field';
    public const LEITWEG_FROM_CUSTOMER_FIELD = 'customer_custom_field';
    public const LEITWEG_FROM_BILLING_COMPANY = 'billing_address_company';

    public const TRIGGER_MANUAL = 'manual';

    public function __construct(
        private readonly SystemConfigService $config,
    ) {
    }

    public function getSellerName(?string $salesChannelId = null): string
    {
        return $this->getString('sellerName', $salesChannelId);
    }

    public function getSellerVatId(?string $salesChannelId = null): string
    {
        return $this->getString('sellerVatId', $salesChannelId);
    }

    public function getSellerStreet(?string $salesChannelId = null): string
    {
        return $this->getString('sellerStreet', $salesChannelId);
    }

    public function getSellerCity(?string $salesChannelId = null): string
    {
        return $this->getString('sellerCity', $salesChannelId);
    }

    public function getSellerPostalCode(?string $salesChannelId = null): string
    {
        return $this->getString('sellerPostalCode', $salesChannelId);
    }

    public function getSellerCountryCode(?string $salesChannelId = null): string
    {
        return $this->getString('sellerCountryCode', $salesChannelId, 'DE');
    }

    public function getSellerEndpointEmail(?string $salesChannelId = null): ?string
    {
        $value = $this->getString('sellerEndpointEmail', $salesChannelId);
        return $value === '' ? null : $value;
    }

    public function getSellerIban(?string $salesChannelId = null): ?string
    {
        $value = $this->getString('sellerIban', $salesChannelId);
        return $value === '' ? null : $value;
    }

    public function getSellerBic(?string $salesChannelId = null): ?string
    {
        $value = $this->getString('sellerBic', $salesChannelId);
        return $value === '' ? null : $value;
    }

    public function getSellerAccountName(?string $salesChannelId = null): ?string
    {
        $value = $this->getString('sellerAccountName', $salesChannelId);
        return $value === '' ? null : $value;
    }

    public function getLeitwegIdSource(?string $salesChannelId = null): string
    {
        return $this->getString('leitwegIdSource', $salesChannelId, self::LEITWEG_FROM_ORDER_FIELD);
    }

    public function requireLeitwegIdForB2g(?string $salesChannelId = null): bool
    {
        return $this->getBool('requireLeitwegIdForB2g', $salesChannelId, true);
    }

    public function getAutoGenerateOnState(?string $salesChannelId = null): string
    {
        return $this->getString('autoGenerateOnState', $salesChannelId, 'completed');
    }

    public function isAutoGenerationEnabled(?string $salesChannelId = null): bool
    {
        return $this->getAutoGenerateOnState($salesChannelId) !== self::TRIGGER_MANUAL;
    }

    public function getOutputDirectory(?string $salesChannelId = null): string
    {
        return $this->getString('outputDirectory', $salesChannelId, 'var/files/xrechnung');
    }

    public function isKositEnabled(?string $salesChannelId = null): bool
    {
        return $this->getBool('kositEnabled', $salesChannelId, false);
    }

    public function isAdminNotificationEnabled(?string $salesChannelId = null): bool
    {
        return $this->getBool('notifyAdminOnInvalid', $salesChannelId, true);
    }

    public function getSlackWebhookUrl(?string $salesChannelId = null): ?string
    {
        $value = $this->getString('slackWebhookUrl', $salesChannelId);
        return $value === '' ? null : $value;
    }

    private function getString(string $key, ?string $salesChannelId, string $default = ''): string
    {
        $value = $this->config->get(self::NS . '.' . $key, $salesChannelId);
        if (is_string($value) && $value !== '') {
            return $value;
        }
        return $default;
    }

    private function getBool(string $key, ?string $salesChannelId, bool $default): bool
    {
        $value = $this->config->get(self::NS . '.' . $key, $salesChannelId);
        if (is_bool($value)) {
            return $value;
        }
        if (is_string($value)) {
            return $value === '1' || strtolower($value) === 'true';
        }
        return $default;
    }
}
