<?php

declare(strict_types=1);

namespace Vineethkrishnan\XrechnungKitShopware\Tests\Peppol;

use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Vineethkrishnan\XrechnungKitShopware\Core\Content\XrechnungInvoice\XrechnungInvoiceDefinition;
use Vineethkrishnan\XrechnungKitShopware\Peppol\PeppolEnvelope;
use Vineethkrishnan\XrechnungKitShopware\Peppol\WebhookPeppolDelivery;
use Vineethkrishnan\XrechnungKitShopware\Service\PluginConfig;

/**
 * Drives WebhookPeppolDelivery through Symfony's MockHttpClient. No
 * Shopware kernel needed - the only Shopware dependency in scope is
 * SystemConfigService, which we mock directly.
 */
final class WebhookPeppolDeliveryTest extends TestCase
{
    private string $tmpXml = '';

    protected function setUp(): void
    {
        $this->tmpXml = tempnam(sys_get_temp_dir(), 'xrechnung-test-') . '.xml';
        file_put_contents($this->tmpXml, '<?xml version="1.0"?><Invoice/>');
    }

    protected function tearDown(): void
    {
        if (is_file($this->tmpXml)) {
            unlink($this->tmpXml);
        }
    }

    public function testIsConfiguredReadsTheWebhookUrl(): void
    {
        $configService = $this->createMock(SystemConfigService::class);
        $configService->method('get')->willReturnCallback(static fn (string $key): mixed => match ($key) {
            'XrechnungKitShopware.config.peppolWebhookUrl' => 'https://ap.example.com/inbound',
            default => null,
        });
        $config = new PluginConfig($configService);

        $delivery = new WebhookPeppolDelivery(new MockHttpClient(), $config, new NullLogger());

        self::assertTrue($delivery->isConfigured(null));
    }

    public function testIsConfiguredFalseWhenWebhookUrlBlank(): void
    {
        $configService = $this->createMock(SystemConfigService::class);
        $configService->method('get')->willReturn(null);
        $config = new PluginConfig($configService);

        $delivery = new WebhookPeppolDelivery(new MockHttpClient(), $config, new NullLogger());

        self::assertFalse($delivery->isConfigured(null));
    }

    public function testDeliverPostsBase64EncodedXmlAndEnvelope(): void
    {
        $configService = $this->createMock(SystemConfigService::class);
        $configService->method('get')->willReturnCallback(static fn (string $key): mixed => match ($key) {
            'XrechnungKitShopware.config.peppolWebhookUrl' => 'https://ap.example.com/inbound',
            'XrechnungKitShopware.config.peppolWebhookBearerToken' => 'tok-123',
            default => null,
        });
        $config = new PluginConfig($configService);

        $capturedRequest = null;
        $mockClient = new MockHttpClient(function (string $method, string $url, array $options) use (&$capturedRequest) {
            $capturedRequest = compact('method', 'url', 'options');
            return new MockResponse(json_encode(['transmissionId' => 'tx-success']), [
                'http_code' => 200,
                'response_headers' => ['Content-Type: application/json'],
            ]);
        });

        $delivery = new WebhookPeppolDelivery($mockClient, $config, new NullLogger());
        $envelope = new PeppolEnvelope('0204:DE123', '0204:DE456', transmissionId: 'tx-1');

        $result = $delivery->deliver($envelope, $this->tmpXml, null);

        self::assertSame(XrechnungInvoiceDefinition::DELIVERY_SENT, $result->status);
        self::assertSame(['transmissionId' => 'tx-success'], $result->response);
        self::assertNotNull($capturedRequest);
        self::assertSame('POST', $capturedRequest['method']);
        self::assertSame('https://ap.example.com/inbound', $capturedRequest['url']);

        $body = json_decode($capturedRequest['options']['body'], true, 32, JSON_THROW_ON_ERROR);
        self::assertSame('0204:DE123', $body['envelope']['senderId']);
        self::assertSame('0204:DE456', $body['envelope']['recipientId']);
        self::assertSame('base64', $body['xml']['encoding']);
        self::assertSame(
            '<?xml version="1.0"?><Invoice/>',
            base64_decode($body['xml']['content'], true),
        );

        $authHeaderFound = false;
        foreach ($capturedRequest['options']['headers'] as $header) {
            if (str_contains($header, 'Authorization: Bearer tok-123')) {
                $authHeaderFound = true;
                break;
            }
        }
        self::assertTrue($authHeaderFound, 'Expected Authorization: Bearer header to be set.');
    }

    public function testDeliverMapsHttp5xxToFailedResult(): void
    {
        $configService = $this->createMock(SystemConfigService::class);
        $configService->method('get')->willReturnCallback(static fn (string $key): mixed => match ($key) {
            'XrechnungKitShopware.config.peppolWebhookUrl' => 'https://ap.example.com/inbound',
            default => null,
        });
        $config = new PluginConfig($configService);

        $mockClient = new MockHttpClient(static fn (): MockResponse => new MockResponse(
            json_encode(['error' => 'AP down']),
            ['http_code' => 503],
        ));

        $delivery = new WebhookPeppolDelivery($mockClient, $config, new NullLogger());
        $envelope = new PeppolEnvelope('0204:DE123', '0204:DE456');

        $result = $delivery->deliver($envelope, $this->tmpXml, null);

        self::assertSame(XrechnungInvoiceDefinition::DELIVERY_FAILED, $result->status);
        self::assertNotNull($result->error);
        self::assertStringContainsString('503', $result->error);
        self::assertSame(['error' => 'AP down'], $result->response);
    }

    public function testDeliverFailsCleanlyWhenXmlMissing(): void
    {
        $configService = $this->createMock(SystemConfigService::class);
        $configService->method('get')->willReturnCallback(static fn (string $key): mixed => match ($key) {
            'XrechnungKitShopware.config.peppolWebhookUrl' => 'https://ap.example.com/inbound',
            default => null,
        });
        $config = new PluginConfig($configService);

        $delivery = new WebhookPeppolDelivery(new MockHttpClient(), $config, new NullLogger());
        $envelope = new PeppolEnvelope('0204:DE123', '0204:DE456');

        $result = $delivery->deliver($envelope, '/nonexistent/path.xml', null);

        self::assertSame(XrechnungInvoiceDefinition::DELIVERY_FAILED, $result->status);
        self::assertStringContainsString('not readable', (string) $result->error);
    }

    public function testDeliverSkippedWhenWebhookUrlBlank(): void
    {
        $configService = $this->createMock(SystemConfigService::class);
        $configService->method('get')->willReturn(null);
        $config = new PluginConfig($configService);

        $delivery = new WebhookPeppolDelivery(new MockHttpClient(), $config, new NullLogger());
        $envelope = new PeppolEnvelope('0204:DE123', '0204:DE456');

        $result = $delivery->deliver($envelope, $this->tmpXml, null);

        self::assertSame(XrechnungInvoiceDefinition::DELIVERY_SKIPPED, $result->status);
    }
}
