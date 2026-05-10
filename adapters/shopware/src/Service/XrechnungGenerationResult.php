<?php

declare(strict_types=1);

namespace Vineethkrishnan\XrechnungKitShopware\Service;

use Vineethkrishnan\XrechnungKitShopware\Core\Content\XrechnungInvoice\XrechnungInvoiceDefinition;

/**
 * Outcome of a single XrechnungService::generate() call.
 *
 * Carries everything the order-state subscriber persists into the
 * xrechnung_kit_invoice table: where the file landed, whether it
 * passed XSD and (optionally) Schematron, the structured error list
 * if any, and provenance fields for audit.
 */
final class XrechnungGenerationResult
{
    /**
     * @param array<int, string> $errors
     */
    public function __construct(
        public readonly string $path,
        public readonly string $status,
        public readonly array $errors,
        public readonly \DateTimeImmutable $generatedAt,
        public readonly string $validatorVersion,
        public readonly string $kositResult,
    ) {
    }

    public function isValid(): bool
    {
        return $this->status === XrechnungInvoiceDefinition::STATUS_GENERATED;
    }

    public function isInvalid(): bool
    {
        return $this->status === XrechnungInvoiceDefinition::STATUS_INVALID;
    }
}
