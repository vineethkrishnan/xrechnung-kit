<?php

declare(strict_types=1);

namespace XrechnungKit\Mapper\Bookings;

use XrechnungKit\Mapping\DocumentPeriod;
use XrechnungKit\Mapping\LineItem;
use XrechnungKit\Mapping\MappingData;
use XrechnungKit\Mapping\Money;
use XrechnungKit\Mapping\TaxBreakdown;
use XrechnungKit\XRechnungTaxCategory;

/**
 * Generic booking-shape mapper. Walks the BookingShapeAdapter SPI and
 * produces a MappingData VO graph; the consumer brings the domain knowledge
 * via their adapter implementation.
 *
 * Scope at v1.0: line aggregation, service-period mapping, basic
 * single-tax-rate breakdown computation. The Locaboo-specific deposit /
 * cancellation / caution branching that lived in the L3 XRechnungDataMapper
 * is NOT yet ported; consumers needing those branches construct the right
 * MappingData::cautionInvoice / depositCancellation / etc. named
 * constructors directly until the next mapper-bookings release.
 *
 * The mapper is intentionally a class rather than a trait or static helper
 * so consumers can decorate / extend it for their own ergonomics. The
 * production builders (seller, buyer, payment means) are abstract in this
 * v0 stub: consumers compose the buildXxx() calls themselves until the
 * mapper-bookings sub-package grows full helpers in a follow-up.
 */
abstract class BookingMapper
{
    public function __construct(protected readonly BookingShapeAdapter $booking)
    {
    }

    abstract public function produce(): MappingData;

    /** @return list<LineItem> */
    protected function buildLines(string $currency): array
    {
        $lines = [];
        foreach ($this->booking->lines() as $bookingLine) {
            $period = null;
            if ($bookingLine->serviceStart !== null && $bookingLine->serviceEnd !== null) {
                $period = new DocumentPeriod($bookingLine->serviceStart, $bookingLine->serviceEnd);
            }
            $lines[] = new LineItem(
                id: $bookingLine->id,
                description: $bookingLine->description,
                quantity: $bookingLine->quantity,
                unitCode: $bookingLine->unitCode,
                unitPrice: Money::of($bookingLine->unitPriceAmount, $currency),
                lineTotal: Money::of($bookingLine->lineTotalAmount, $currency),
                taxCategory: XRechnungTaxCategory::STANDARD_RATE,
                taxPercent: $bookingLine->taxPercent,
                name: $bookingLine->resourceName,
                period: $period,
            );
        }
        return $lines;
    }

    /**
     * Builds the document-level TaxBreakdown rows by grouping booking lines
     * by tax percent and summing the line-total amounts as the taxable
     * amount and computing the tax amount via float arithmetic. Acceptable
     * precision for amounts under ~1M EUR per architecture section 15; a
     * future Money arithmetic layer can replace the float hop.
     *
     * @param list<LineItem> $lines
     * @return list<TaxBreakdown>
     */
    protected function buildTaxBreakdowns(array $lines, string $currency): array
    {
        /** @var array<string, array{taxable: float, tax: float}> $byPercent */
        $byPercent = [];
        foreach ($lines as $line) {
            $key = $line->taxPercent;
            if (!isset($byPercent[$key])) {
                $byPercent[$key] = ['taxable' => 0.0, 'tax' => 0.0];
            }
            $taxable = (float) $line->lineTotal->amount;
            $byPercent[$key]['taxable'] += $taxable;
            $byPercent[$key]['tax'] += $taxable * ((float) $key / 100);
        }
        ksort($byPercent);

        $breakdowns = [];
        foreach ($byPercent as $percent => $sums) {
            $breakdowns[] = new TaxBreakdown(
                category: XRechnungTaxCategory::STANDARD_RATE,
                percent: $percent,
                taxableAmount: Money::of(number_format($sums['taxable'], 2, '.', ''), $currency),
                taxAmount: Money::of(number_format($sums['tax'], 2, '.', ''), $currency),
            );
        }
        return $breakdowns;
    }
}
