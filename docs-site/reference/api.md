---
title: API reference
description: A curated reference to the public surface of `vineethkrishnan/xrechnung-kit-core`. Covers the mapping value objects, the builder, the generator, the validator, and the enums.
---

# API overview

This page is a hand-curated overview of the public types in `xrechnung-kit-core`. It is intentionally narrative: which types belong together, which constructors enforce what, where the Internal namespace begins.

For the per-class auto-generated reference produced by phpDocumentor, see [Generated API reference](/api/) - it is regenerated on every push to `main`. Anything under `XrechnungKit\Internal\` is implementation detail and is not covered by SemVer; everything else is part of the public contract.

## At a glance

| Surface | Namespace | Purpose |
| ------- | --------- | ------- |
| Mapping value objects | `XrechnungKit\Mapping` | The single public input contract. See [`MappingData`](#mappingdata). |
| Document classes | `XrechnungKit` | UNTDID code enums driving document-class selection. |
| Builder | `XrechnungKit\Builder\XRechnungBuilder` | Bridges `MappingData` to the lifted entity pipeline. |
| Generator | `XrechnungKit\XRechnungGenerator` | Renders the entity into UBL XML and validates atomically. |
| Validator | `XrechnungKit\XRechnungValidator` | Standalone UBL XSD and (optionally) KoSIT Schematron passes. |

## Mapping value objects

### `MappingData`

Namespace: `XrechnungKit\Mapping\MappingData`

The root of the public input contract. Construct via one of five named constructors; never via `new`.

```php
MappingData::standardInvoice(
    DocumentMeta $meta,
    Party $seller,
    Party $buyer,
    /** @var LineItem[] */ array $lines,
    /** @var TaxBreakdown[] */ array $taxes,
    /** @var PaymentMeans[] */ array $payment,
    DocumentTotals $totals,
    ?BillingReference $billingReference = null,
    ?DeliveryInfo $delivery = null,
): self;

MappingData::partialInvoice(/* same shape */): self;
MappingData::cautionInvoice(/* same shape */): self;
MappingData::creditNote(/* same shape, plus required prior-invoice ref */): self;
MappingData::depositCancellation(/* same shape, plus required prior-invoice ref */): self;
```

Constructors validate at the point of construction: missing required fields, currency mismatches between line items and totals, and inconsistent tax breakdowns all throw `\InvalidArgumentException` synchronously.

See [Mapping data contract](/mapping-data) for the full per-field semantics.

### Field-level value objects

| Class | Required fields | Notes |
| ----- | --------------- | ----- |
| `DocumentMeta` | `invoiceNumber`, `type`, `issueDate`, `currency` | `dueDate`, `buyerReference`, `note`, `purchaseOrderRef` are optional. |
| `Party` | constructed via `Party::business()` or `Party::publicAdministration()` | Public administration variant requires `leitwegId`. |
| `Address` | `street`, `city`, `zip`, `countryCode` | `additionalStreet`, `additionalCityInfo` are optional. |
| `Contact` | none required | Email, phone, name are all optional but at least one should be set. |
| `LineItem` | `id`, `description`, `quantity`, `unitCode`, `unitPrice`, `lineTotal`, `taxCategory`, `taxPercent` | `name`, `sellerItemId`, `buyerItemId`, `period` are optional. |
| `Money` | constructed via `Money::eur(string $amount)` for EUR | Other currencies via `new Money(string $amount, string $currency)`. Amounts are decimal strings, never floats. |
| `TaxBreakdown` | `category`, `percent`, `taxableAmount`, `taxAmount` | One per `(category, percent)` combination. |
| `PaymentMeans` | constructed via static factories: `sepaCreditTransfer()`, `sepaDirectDebit()`, `wireTransfer()`, etc. | Seven payment-code variants supported. |
| `TaxId` | constructed via `TaxId::vatId('DE...')` or `TaxId::companyId('...')` | Maps to UBL `<PartyTaxScheme>`. |
| `DocumentTotals` | `lineNet`, `taxableAmount`, `taxAmount`, `payable` | Currencies must match `DocumentMeta::currency`. |
| `BillingReference` | `invoiceNumber`, `issueDate` | Required on credit notes and deposit cancellations. |
| `DeliveryInfo` | `actualDeliveryDate` or `period` | Optional. |

## Document classes

### `XRechnungInvoiceTypeCode`

Namespace: `XrechnungKit\XRechnungInvoiceTypeCode`

Backed PHP 8.1 enum mapping document classes to UNTDID codes.

| Case | UNTDID code | Used by |
| ---- | ----------- | ------- |
| `COMMERCIAL_INVOICE` | 380 | `MappingData::standardInvoice()` |
| `PARTIAL_INVOICE` | 326 | `MappingData::partialInvoice()`, `MappingData::cautionInvoice()` |
| `CREDIT_NOTE` | 381 | `MappingData::creditNote()`, `MappingData::depositCancellation()` |
| `REQUEST_FOR_PAYMENT` | 71 | reserved |

### `XRechnungTaxCategory`

Namespace: `XrechnungKit\XRechnungTaxCategory`

| Case | UBL code | Description |
| ---- | -------- | ----------- |
| `STANDARD_RATE` | `S` | Standard German VAT (typically 19%) |
| `REDUCED_RATE` | `AA` | Reduced rate (typically 7%) |
| `ZERO_RATED_GOODS` | `Z` | Zero-rated supply |
| `EXEMPT` | `E` | VAT exempt |
| `REVERSE_CHARGE` | `AE` | Reverse charge applies |
| `INTRA_COMMUNITY` | `K` | Intra-Community supply |
| `EXPORT` | `G` | Export outside the EU |
| `NOT_SUBJECT_TO_TAX` | `O` | Services outside scope |

## Builder

### `XRechnungBuilder::buildEntity()`

Namespace: `XrechnungKit\Builder\XRechnungBuilder`

```php
public static function buildEntity(MappingData $mapping): XRechnungEntity;
```

Pure transformation: a `MappingData` in, an `XRechnungEntity` out. The entity is the type the generator's UBL template expects. No I/O, no globals, no logging. Safe to call inside a tight loop.

If the mapping is internally inconsistent (currency mismatches, tax breakdown sums that disagree with the line totals, etc.), the builder throws `\InvalidArgumentException`.

## Generator

### `XRechnungGenerator`

Namespace: `XrechnungKit\XRechnungGenerator`

```php
public function __construct(XRechnungEntity $entity);

public function generateXRechnung(string $targetPath): string;
```

Renders the entity into UBL XML, runs UBL XSD validation in memory, then writes atomically:

- On success: writes to `$targetPath`. Returns `$targetPath`.
- On failure: writes to `${dirname}/${basename}_invalid.${ext}` instead. Returns that path.

Atomic write means: write to `${path}.tmp`, fsync, rename to final path. The opposite-sibling file (the `_invalid` if you just wrote a valid one, or vice versa) is removed in the same atomic operation. You cannot end up with both a `valid.xml` and a `valid_invalid.xml` for the same invoice number.

## Validator

### `XRechnungValidator`

Namespace: `XrechnungKit\XRechnungValidator`

```php
public function __construct(?LoggerInterface $logger = null);

public function validate(string $path): bool;
public function validateSchematron(string $path): bool;
public function getErrors(): array;
```

`validate()` runs UBL XSD validation against the bundled UBL Invoice 2.4 / CreditNote 2.4 schemas. Returns `true` on pass.

`validateSchematron()` runs the optional KoSIT Schematron pass. Requires `vineethkrishnan/xrechnung-kit-kosit-bundle` to be installed and Java 17+ to be available at validation time. Returns `true` on pass.

`getErrors()` returns structured error messages from the most recent validation. Format is suitable for direct display to operators.

## Internal types

Anything under `XrechnungKit\Internal\` is implementation detail. Specifically `XRechnungEntity`, `Internal\AtomicWriter`, `Internal\AlertGate`, and the lifted L3 entity classes are not part of the public contract and may move freely between minor releases.

If you need to subclass or replace one of those, open an issue describing the use case and we will lift it into the public surface intentionally.
