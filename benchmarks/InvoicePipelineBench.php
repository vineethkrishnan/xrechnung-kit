<?php

declare(strict_types=1);

namespace XrechnungKit\Benchmarks;

use PhpBench\Attributes\AfterMethods;
use PhpBench\Attributes\BeforeMethods;
use PhpBench\Attributes\Iterations;
use PhpBench\Attributes\OutputTimeUnit;
use PhpBench\Attributes\Revs;
use PhpBench\Attributes\Subject;
use XrechnungKit\Builder\XRechnungBuilder;
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
use XrechnungKit\XRechnungGenerator;
use XrechnungKit\XRechnungInvoiceTypeCode;
use XrechnungKit\XRechnungTaxCategory;

/**
 * End-to-end pipeline benchmark for the architecture's headline target:
 * a 50-line invoice generated in under 50ms wall on a 2024 laptop.
 *
 * Each subject runs the full path: MappingData (constructed in setUp once)
 * -> XRechnungBuilder -> XRechnungGenerator (template substitution +
 * in-memory XSD validation + AtomicWriter to a tempfile).
 *
 * The MappingData construction itself runs in setUp so the benchmark
 * reports the cost of the pipeline, not the cost of building 50 LineItem
 * value objects which the consumer would do once per invoice anyway.
 */
#[OutputTimeUnit('milliseconds', precision: 3)]
#[BeforeMethods(['setUp'])]
#[AfterMethods(['tearDown'])]
final class InvoicePipelineBench
{
    private MappingData $mapping;
    private string $outputPath;

    public function setUp(): void
    {
        $this->mapping = $this->build50LineMapping();
        $this->outputPath = sys_get_temp_dir() . '/xrechnung-kit-bench-' . uniqid('', true) . '.xml';
    }

    public function tearDown(): void
    {
        if (file_exists($this->outputPath)) {
            @unlink($this->outputPath);
        }
        $invalid = preg_replace('/\.xml$/', '_invalid.xml', $this->outputPath);
        if (\is_string($invalid) && file_exists($invalid)) {
            @unlink($invalid);
        }
    }

    #[Subject]
    #[Revs(20)]
    #[Iterations(3)]
    public function benchFullPipelineFiftyLines(): void
    {
        $entity = XRechnungBuilder::buildEntity($this->mapping);
        $generator = new XRechnungGenerator($entity);
        $generator->generateXRechnung($this->outputPath);
    }

    #[Subject]
    #[Revs(50)]
    #[Iterations(3)]
    public function benchBuilderOnly(): void
    {
        XRechnungBuilder::buildEntity($this->mapping);
    }

    private function build50LineMapping(): MappingData
    {
        $lines = [];
        $netCents = 0;
        for ($i = 1; $i <= 50; $i++) {
            $unitPriceCents = 10000;
            $netCents += $unitPriceCents;
            $lines[] = new LineItem(
                id: (string) $i,
                description: "Beratungsleistung Position {$i}",
                quantity: '1',
                unitCode: 'HUR',
                unitPrice: Money::eur('100.00'),
                lineTotal: Money::eur('100.00'),
                taxCategory: XRechnungTaxCategory::STANDARD_RATE,
                taxPercent: '19',
                name: "Hour {$i}",
            );
        }

        $netStr = number_format($netCents / 100, 2, '.', '');
        $taxStr = number_format(($netCents / 100) * 0.19, 2, '.', '');
        $payableStr = number_format(($netCents / 100) * 1.19, 2, '.', '');

        return MappingData::standardInvoice(
            meta: new DocumentMeta(
                invoiceNumber: 'BENCH-001',
                type: XRechnungInvoiceTypeCode::COMMERCIAL_INVOICE,
                issueDate: new \DateTimeImmutable('2026-05-09'),
                currency: 'EUR',
                buyerReference: '04011000-12345-67',
            ),
            seller: Party::business(
                name: 'Beispiel Lieferant GmbH',
                address: new Address('Lieferantenstr. 1', 'Berlin', '10115', 'DE'),
                taxId: TaxId::vatId('DE123456789'),
            ),
            buyer: Party::publicAdministration(
                name: 'Bundesamt fuer Beispielzwecke',
                address: new Address('Behoerdenweg 7', 'Bonn', '53113', 'DE'),
                leitwegId: '04011000-12345-67',
            ),
            lines: $lines,
            taxes: [
                new TaxBreakdown(
                    category: XRechnungTaxCategory::STANDARD_RATE,
                    percent: '19',
                    taxableAmount: Money::eur($netStr),
                    taxAmount: Money::eur($taxStr),
                ),
            ],
            payment: [PaymentMeans::sepaCreditTransfer('DE12500105170648489890')],
            totals: new DocumentTotals(
                lineNet: Money::eur($netStr),
                taxableAmount: Money::eur($netStr),
                taxAmount: Money::eur($taxStr),
                payable: Money::eur($payableStr),
            ),
        );
    }
}
