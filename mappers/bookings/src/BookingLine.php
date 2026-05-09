<?php

declare(strict_types=1);

namespace XrechnungKit\Mapper\Bookings;

/**
 * One chargeable line returned by the BookingShapeAdapter. Carries enough
 * to construct an XrechnungKit\Mapping\LineItem without the mapper needing
 * to know anything about the consumer's domain.
 *
 * Decimal fields are decimal strings (matching Money's storage) so
 * arithmetic precision stays in the consumer's hands.
 */
final class BookingLine
{
    public function __construct(
        public readonly string $id,
        public readonly string $description,
        public readonly string $quantity,
        public readonly string $unitCode,
        public readonly string $unitPriceAmount,
        public readonly string $lineTotalAmount,
        public readonly string $taxPercent,
        public readonly ?\DateTimeImmutable $serviceStart = null,
        public readonly ?\DateTimeImmutable $serviceEnd = null,
        public readonly ?string $resourceName = null,
    ) {
    }
}
