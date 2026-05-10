<?php

declare(strict_types=1);

namespace Vineethkrishnan\XrechnungKitShopware\Peppol;

use Vineethkrishnan\XrechnungKitShopware\Core\Content\XrechnungInvoice\XrechnungInvoiceDefinition;

/**
 * Outcome of a single PEPPOL delivery attempt.
 *
 * The xrechnung_kit_invoice row persists this directly: status maps to
 * delivery_status, response to delivery_response (parsed JSON), error to
 * delivery_error, attemptedAt to delivery_attempted_at.
 */
final class PeppolDeliveryResult
{
    /**
     * @param array<string, mixed>|null $response
     */
    public function __construct(
        public readonly string $status,
        public readonly ?array $response,
        public readonly ?string $error,
        public readonly \DateTimeImmutable $attemptedAt,
    ) {
    }

    /**
     * @param array<string, mixed>|null $response
     */
    public static function sent(?array $response): self
    {
        return new self(
            status: XrechnungInvoiceDefinition::DELIVERY_SENT,
            response: $response,
            error: null,
            attemptedAt: new \DateTimeImmutable(),
        );
    }

    public static function failed(string $error, ?array $response = null): self
    {
        return new self(
            status: XrechnungInvoiceDefinition::DELIVERY_FAILED,
            response: $response,
            error: substr($error, 0, 2000),
            attemptedAt: new \DateTimeImmutable(),
        );
    }

    public static function skipped(string $reason): self
    {
        return new self(
            status: XrechnungInvoiceDefinition::DELIVERY_SKIPPED,
            response: null,
            error: $reason,
            attemptedAt: new \DateTimeImmutable(),
        );
    }
}
