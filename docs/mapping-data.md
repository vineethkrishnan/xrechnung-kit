# Mapping data contract

`MappingData` is the public input contract of xrechnung-kit. It is a typed, immutable value-object graph. Once constructed, it is structurally trusted by every downstream stage. The constructors are the only validation point.

This document is the canonical reference. The IDE is the second.

## Top-level shape

```
MappingData
 |- DocumentMeta            (number, type, issueDate, dueDate, currency, buyerReference)
 |- Party seller            (name, address, taxId, contact)
 |- Party buyer             (name, address, leitwegId?, contact)
 |- LineItem[] lines        (each: id, description, qty, unitCode, netPrice, taxCategory, taxPercent, period?)
 |- TaxBreakdown[] taxes    (per (category, percent): taxableAmount, taxAmount)
 |- PaymentMeans[] payment  (code 58|59|54|30|48|10|42 + variant payload)
 |- DocumentTotals          (lineNet, allowance, charge, taxableAmount, taxAmount, payable)
 |- DocumentPeriod? period  (BG-14)
 |- BillingReference? prior (for credit notes: original invoice id + issueDate)
 |- Attachment[] attachments (filename, mime, bytes, description?)
 |- Note[] notes
```

## Named constructors

Use the named constructor that matches your document class. Each runs the right combination of business-rule checks.

| Constructor | Document class (UNTDID 1001) | XRechnung BR mapping |
|---|---|---|
| `MappingData::standardInvoice(...)` | INVOICE_380 | full BR-CO-* arithmetic, optional BG-14 |
| `MappingData::partialInvoice(...)` | PARTIAL_326 (Anzahlung) | requires BG-14 (BR-DE-TMP-32) |
| `MappingData::cautionInvoice(...)` | INVOICE_380 (caution variant) | requires BG-14, BT-9 |
| `MappingData::creditNote(...)` | CREDIT_NOTE_381 | requires BG-3 / BT-25 (BR-DE-22) |
| `MappingData::depositCancellation(...)` | CREDIT_NOTE_381 (deposit cancel) | requires both BG-3 and BG-14 |

Telescoping constructors are intentionally absent. If your input combination does not fit, file a feature request before forcing it through `__construct`.

## Enums

| Enum | Values | Purpose |
|---|---|---|
| `XRechnungInvoiceTypeCode` | INVOICE_380, PARTIAL_326, CREDIT_NOTE_381, ... | UNTDID 1001 subset |
| `XRechnungTaxCategory` | STANDARD_S, ZERO_Z, EXEMPT_E, REVERSE_AE, INTRA_EU_K, ... | EN 16931 BG-23 codes |
| `PaymentMeansCode` | SEPA_CT_58, SEPA_DD_59, CARD_54, CARD_30, CARD_48, CASH_10, BANK_42 | UNTDID 4461 subset |
| `DocumentClass` | INVOICE, CREDIT_NOTE | drives template + UBL envelope selection |

Enums are exhaustive over what xrechnung-kit emits. Adding a value is a minor bump if it preserves emitted XML for existing inputs; otherwise a major.

## Validation at construction

Each value object validates its inputs in the constructor and throws `MappingDataException` with a precise message. Examples:

- Negative `qty` on a `LineItem` -> `MappingDataException: line item qty must be >= 0, got -1`
- Invalid Leitweg-ID format -> `MappingDataException: Leitweg-ID does not match /^\d{2,12}-[A-Za-z0-9]{1,30}-\d{2}$/`
- Credit note without `BillingReference` -> `MappingDataException: BR-DE-22: credit note requires a prior invoice reference`
- TaxBreakdown sum drifts >1 cent from per-line tax sum -> `MappingDataException: BR-CO-18: tax breakdown drift 0.03 EUR exceeds 1 cent tolerance`

Once construction succeeds, downstream stages do not re-validate structure. They trust the boundary.

## A worked example

```php
<?php
use XrechnungKit\Mapping\{MappingData, DocumentMeta, Party, LineItem, Money, Address, TaxId, PaymentMeans};
use XrechnungKit\Mapping\Enum\{XRechnungInvoiceTypeCode, XRechnungTaxCategory};

$mapping = MappingData::standardInvoice(
    meta: new DocumentMeta(
        number:         'RE-2026-0001',
        type:           XRechnungInvoiceTypeCode::INVOICE_380,
        issueDate:      new DateTimeImmutable('2026-05-01'),
        dueDate:        new DateTimeImmutable('2026-05-31'),
        currency:       'EUR',
        buyerReference: '04011000-12345-67',
    ),
    seller: Party::business(
        name:    'Beispiel GmbH',
        address: new Address('Musterstr. 1', '10115', 'Berlin', 'DE'),
        taxId:   TaxId::vatId('DE123456789'),
    ),
    buyer: Party::publicAdministration(
        name:       'Bundesamt fuer XYZ',
        address:    new Address('Behoerdenweg 7', '53113', 'Bonn', 'DE'),
        leitwegId:  '04011000-12345-67',
    ),
    lines: [
        new LineItem(
            id:           '1',
            description:  'Beratungsleistung',
            qty:          4.0,
            unitCode:     'HUR',
            netPrice:     Money::eur('120.00'),
            taxCategory:  XRechnungTaxCategory::STANDARD_S,
            taxPercent:   '19.00',
        ),
    ],
    payment: [
        PaymentMeans::sepaCreditTransfer(
            iban:        'DE12500105170648489890',
            bic:         'INGDDEFFXXX',
            accountName: 'Beispiel GmbH',
        ),
    ],
);
```

## Custom mappers

If your domain shape does not match either reference mapper (`mapper-simple`, `mapper-bookings`), implement `SourceMapperInterface` directly:

```php
final class MyInvoiceMapper implements SourceMapperInterface
{
    public function __construct(private readonly MyDomainInvoice $invoice) {}

    public function produce(): MappingData
    {
        return MappingData::standardInvoice(
            meta:    $this->buildMeta(),
            seller:  $this->buildSeller(),
            buyer:   $this->buildBuyer(),
            lines:   $this->buildLines(),
            payment: $this->buildPayment(),
        );
    }
}
```

The library never inspects your domain. The mapper is the only place where domain-specific knowledge lives.

## Stability invariants

- Adding an optional VO field: minor bump.
- Adding a required VO field or constructor parameter: major bump.
- Tightening validation that rejects previously-accepted inputs: major bump.
- Loosening validation that accepts new inputs: minor bump.

See [policies.md](policies.md) for the full SemVer table.
