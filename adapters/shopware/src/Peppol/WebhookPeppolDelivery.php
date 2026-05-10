<?php

declare(strict_types=1);

namespace Vineethkrishnan\XrechnungKitShopware\Peppol;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface as HttpClientExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Vineethkrishnan\XrechnungKitShopware\Service\PluginConfig;

/**
 * Reference PEPPOL delivery implementation.
 *
 * POSTs a JSON body containing the envelope and a base64 of the
 * XRechnung XML to a configurable webhook URL. Suited to operators
 * whose AP relay (or their own edge service that speaks PEPPOL via
 * AS4) accepts an inbound webhook in this shape:
 *
 *   POST {webhookUrl}
 *   Authorization: Bearer {token}            (optional)
 *   Content-Type: application/json
 *
 *   {
 *     "envelope": {
 *       "senderId": "0204:DE123456789",
 *       "recipientId": "0204:04011000-12345-67",
 *       "documentType": "...",
 *       "process": "...",
 *       "transmissionId": "..."
 *     },
 *     "xml": {
 *       "filename": "RE-1.xml",
 *       "encoding": "base64",
 *       "content": "PD94bWw..."
 *     }
 *   }
 *
 * The webhook is expected to respond with a JSON body. Anything in the
 * 2xx range is treated as success; the body is persisted verbatim into
 * delivery_response for audit. 4xx and 5xx, transport errors, and
 * malformed JSON all map to PeppolDeliveryResult::failed().
 */
final class WebhookPeppolDelivery implements PeppolDeliveryInterface
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly PluginConfig $config,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function name(): string
    {
        return 'webhook';
    }

    public function isConfigured(?string $salesChannelId): bool
    {
        $url = $this->config->getPeppolWebhookUrl($salesChannelId);
        return $url !== null && $url !== '';
    }

    public function deliver(PeppolEnvelope $envelope, string $xmlPath, ?string $salesChannelId = null): PeppolDeliveryResult
    {
        if (!is_file($xmlPath) || !is_readable($xmlPath)) {
            return PeppolDeliveryResult::failed(sprintf('XML file not readable: %s', $xmlPath));
        }
        $url = $this->config->getPeppolWebhookUrl($salesChannelId);
        if ($url === null || $url === '') {
            return PeppolDeliveryResult::skipped('Webhook URL is not configured.');
        }

        $payload = [
            'envelope' => $envelope->toArray(),
            'xml' => [
                'filename' => basename($xmlPath),
                'encoding' => 'base64',
                'content' => base64_encode((string) file_get_contents($xmlPath)),
            ],
        ];

        $headers = ['Content-Type' => 'application/json'];
        $token = $this->config->getPeppolWebhookBearerToken($salesChannelId);
        if ($token !== null && $token !== '') {
            $headers['Authorization'] = sprintf('Bearer %s', $token);
        }

        try {
            $response = $this->httpClient->request('POST', $url, [
                'headers' => $headers,
                'json' => $payload,
                'timeout' => 30,
            ]);

            $statusCode = $response->getStatusCode();
            $rawBody = $response->getContent(false);
            $parsedBody = $this->safeJsonDecode($rawBody);

            if ($statusCode >= 200 && $statusCode < 300) {
                return PeppolDeliveryResult::sent($parsedBody ?? ['status' => $statusCode, 'raw' => $rawBody]);
            }

            return PeppolDeliveryResult::failed(
                sprintf('Webhook returned HTTP %d', $statusCode),
                $parsedBody ?? ['status' => $statusCode, 'raw' => $rawBody],
            );
        } catch (HttpClientExceptionInterface $e) {
            $this->logger->warning('xrechnung-kit-shopware: peppol webhook transport error', [
                'exception' => $e::class,
                'message' => $e->getMessage(),
            ]);
            return PeppolDeliveryResult::failed($e->getMessage());
        } catch (\Throwable $e) {
            $this->logger->error('xrechnung-kit-shopware: peppol webhook unexpected error', [
                'exception' => $e::class,
                'message' => $e->getMessage(),
            ]);
            return PeppolDeliveryResult::failed($e->getMessage());
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    private function safeJsonDecode(string $body): ?array
    {
        if ($body === '') {
            return null;
        }
        try {
            $decoded = json_decode($body, true, 32, JSON_THROW_ON_ERROR);
            return is_array($decoded) ? $decoded : null;
        } catch (\JsonException) {
            return null;
        }
    }
}
