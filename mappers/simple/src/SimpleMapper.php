<?php

declare(strict_types=1);

namespace XrechnungKit\Mapper\Simple;

use XrechnungKit\Mapping\Address;
use XrechnungKit\Mapping\DocumentMeta;
use XrechnungKit\Mapping\DocumentTotals;
use XrechnungKit\Mapping\LineItem;
use XrechnungKit\Mapping\MappingData;
use XrechnungKit\Mapping\Money;
use XrechnungKit\Mapping\Party;
use XrechnungKit\Mapping\PaymentMeans;
use XrechnungKit\Mapping\TaxBreakdown;
use XrechnungKit\Mapping\TaxId;
use XrechnungKit\XRechnungInvoiceTypeCode;
use XrechnungKit\XRechnungTaxCategory;

/**
 * Reference mapper that turns a single-tax-rate flat associative array into
 * a MappingData value-object graph. Useful as a greenfield starting point
 * and as the docs example for docs/mapping-data.md; production consumers
 * should write their own SourceMapper that walks their domain model
 * directly rather than going through a generic array shape.
 *
 * Expected array shape:
 *
 *   [
 *     'invoiceNumber'    => 'RE-2026-0001',
 *     'issueDate'        => '2026-05-09',
 *     'currency'         => 'EUR',
 *     'leitwegId'        => '04011000-12345-67',
 *     'taxPercent'       => '19.00',
 *     'seller' => [
 *       'name'     => 'Beispiel GmbH',
 *       'street'   => 'Musterstr. 1',
 *       'city'     => 'Berlin',
 *       'zip'      => '10115',
 *       'country'  => 'DE',
 *       'vatId'    => 'DE123456789',
 *     ],
 *     'buyer' => [
 *       'name'     => 'Bundesamt fuer XYZ',
 *       'street'   => 'Behoerdenweg 7',
 *       'city'     => 'Bonn',
 *       'zip'      => '53113',
 *       'country'  => 'DE',
 *     ],
 *     'iban' => 'DE12500105170648489890',
 *     'lines' => [
 *       ['id' => '1', 'description' => 'Beratungsleistung',
 *        'quantity' => '4', 'unitCode' => 'HUR',
 *        'unitPrice' => '120.00', 'lineTotal' => '480.00'],
 *     ],
 *   ]
 */
final class SimpleMapper
{
    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): MappingData
    {
        $currency = (string) ($data['currency'] ?? 'EUR');
        $taxPercent = (string) ($data['taxPercent'] ?? '19.00');

        $lineNetCents = 0;
        $lines = [];
        foreach ((array) ($data['lines'] ?? []) as $i => $line) {
            $lineTotal = (string) ($line['lineTotal'] ?? '0.00');
            $lines[] = new LineItem(
                id: (string) ($line['id'] ?? (string) ($i + 1)),
                description: (string) ($line['description'] ?? ''),
                quantity: (string) ($line['quantity'] ?? '1'),
                unitCode: (string) ($line['unitCode'] ?? 'EA'),
                unitPrice: Money::of((string) ($line['unitPrice'] ?? '0.00'), $currency),
                lineTotal: Money::of($lineTotal, $currency),
                taxCategory: XRechnungTaxCategory::STANDARD_RATE,
                taxPercent: $taxPercent,
            );
            $lineNetCents += (int) round((float) $lineTotal * 100);
        }

        $netStr = number_format($lineNetCents / 100, 2, '.', '');
        $taxStr = number_format(($lineNetCents / 100) * ((float) $taxPercent / 100), 2, '.', '');
        $payableStr = number_format(($lineNetCents / 100) + ((float) $taxStr), 2, '.', '');

        $seller = (array) ($data['seller'] ?? []);
        $buyer = (array) ($data['buyer'] ?? []);

        return MappingData::standardInvoice(
            meta: new DocumentMeta(
                invoiceNumber: (string) ($data['invoiceNumber'] ?? 'RE-0001'),
                type: XRechnungInvoiceTypeCode::COMMERCIAL_INVOICE,
                issueDate: new \DateTimeImmutable((string) ($data['issueDate'] ?? 'today')),
                currency: $currency,
                buyerReference: isset($data['leitwegId']) ? (string) $data['leitwegId'] : null,
            ),
            seller: Party::business(
                name: (string) ($seller['name'] ?? ''),
                address: new Address(
                    street: (string) ($seller['street'] ?? ''),
                    city: (string) ($seller['city'] ?? ''),
                    zip: (string) ($seller['zip'] ?? ''),
                    countryCode: (string) ($seller['country'] ?? 'DE'),
                ),
                taxId: isset($seller['vatId']) ? TaxId::vatId((string) $seller['vatId']) : null,
            ),
            buyer: isset($data['leitwegId'])
                ? Party::publicAdministration(
                    name: (string) ($buyer['name'] ?? ''),
                    address: new Address(
                        street: (string) ($buyer['street'] ?? ''),
                        city: (string) ($buyer['city'] ?? ''),
                        zip: (string) ($buyer['zip'] ?? ''),
                        countryCode: (string) ($buyer['country'] ?? 'DE'),
                    ),
                    leitwegId: (string) $data['leitwegId'],
                )
                : Party::business(
                    name: (string) ($buyer['name'] ?? ''),
                    address: new Address(
                        street: (string) ($buyer['street'] ?? ''),
                        city: (string) ($buyer['city'] ?? ''),
                        zip: (string) ($buyer['zip'] ?? ''),
                        countryCode: (string) ($buyer['country'] ?? 'DE'),
                    ),
                ),
            lines: $lines,
            taxes: [
                new TaxBreakdown(
                    category: XRechnungTaxCategory::STANDARD_RATE,
                    percent: $taxPercent,
                    taxableAmount: Money::of($netStr, $currency),
                    taxAmount: Money::of($taxStr, $currency),
                ),
            ],
            payment: [
                PaymentMeans::sepaCreditTransfer((string) ($data['iban'] ?? '')),
            ],
            totals: new DocumentTotals(
                lineNet: Money::of($netStr, $currency),
                taxableAmount: Money::of($netStr, $currency),
                taxAmount: Money::of($taxStr, $currency),
                payable: Money::of($payableStr, $currency),
            ),
        );
    }
}
