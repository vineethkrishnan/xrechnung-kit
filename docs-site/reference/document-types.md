---
title: Document type codes
description: UNTDID document type codes recognised by xrechnung-kit, with the mapping to MappingData named constructors and the standard German use cases.
---

# Document type codes

XRechnung relies on the UNTDID 1001 code list for document type identification. xrechnung-kit recognises the codes below and provides a `MappingData` named constructor for each. The mapping is direct: you do not pick a code, you pick a constructor.

## Supported codes

| UNTDID | German term | English term | `MappingData` constructor |
| ------ | ----------- | ------------ | ------------------------- |
| 380 | Rechnung | Commercial invoice | `MappingData::standardInvoice()` |
| 326 | Teilrechnung | Partial invoice | `MappingData::partialInvoice()` |
| 326 | Anzahlungsrechnung | Deposit invoice | `MappingData::cautionInvoice()` |
| 381 | Stornorechnung / Gutschrift | Credit note | `MappingData::creditNote()` |
| 381 | Stornorechnung Anzahlung | Deposit cancellation | `MappingData::depositCancellation()` |

The two named constructors that share a code (326 for partial vs. caution, 381 for credit note vs. deposit cancellation) emit identical UNTDID codes but enforce different MappingData shapes:

- `cautionInvoice()` carries an explicit "this is a security deposit" semantic and disallows a `BillingReference` to a prior invoice (a caution stands alone).
- `depositCancellation()` requires a `BillingReference` to the original deposit invoice and an explicit reversal amount.

## When to pick which

| Scenario | Constructor | UNTDID | Notes |
| -------- | ----------- | ------ | ----- |
| Standard one-off invoice for goods or services | `standardInvoice` | 380 | The default. |
| Partial invoice in a sequence (e.g. consulting milestone billing) | `partialInvoice` | 326 | Tax is recognised on each partial. |
| Anzahlung / advance payment requested before delivery | `cautionInvoice` | 326 | KoSIT enforces specific text constraints on this. |
| Cancellation of an issued invoice (returns, dispute resolution) | `creditNote` | 381 | Requires `BillingReference` to the original invoice. |
| Cancellation of a previously billed deposit | `depositCancellation` | 381 | Requires `BillingReference` to the original deposit. |

## What gets emitted

The selected code lands in `<cbc:InvoiceTypeCode>` for invoice-like documents and `<cbc:CreditNoteTypeCode>` for credit-note-like documents. The XML root element is `<ubl:Invoice>` for codes 380 and 326, and `<ubl:CreditNote>` for code 381. xrechnung-kit selects the correct UBL schema (Invoice 2.4 vs CreditNote 2.4) for in-memory XSD validation automatically based on the code.

## Codes deliberately not supported

The following UNTDID codes are not currently exposed by xrechnung-kit. They are valid in principle but lack a clear German use case at this time:

- **71** Request for payment - reserved as `XRechnungInvoiceTypeCode::REQUEST_FOR_PAYMENT` but no `MappingData` constructor.
- **325** Pro forma invoice - not a billable document under XRechnung.
- **383** Debit note - rare in German B2B/B2G invoicing; use a follow-up `standardInvoice` instead.

If your domain requires one of these, open an issue with the German term, the typical use case, and the KoSIT scenario it should pass under.

## Mapping back from XML

If you need to identify the document class from a parsed XML rather than from the source `MappingData`:

```php
$invoiceTypeCode = $dom->getElementsByTagName('InvoiceTypeCode')[0]?->nodeValue
    ?? $dom->getElementsByTagName('CreditNoteTypeCode')[0]?->nodeValue;

$class = match ($invoiceTypeCode) {
    '380' => 'standard invoice',
    '326' => 'partial invoice or caution',  // disambiguate by semantics
    '381' => 'credit note or deposit cancellation',
    default => throw new \UnexpectedValueException("Unknown UNTDID: $invoiceTypeCode"),
};
```

xrechnung-kit does not provide a parser; this library only generates and validates. For round-tripping XML back into `MappingData`, use [horstoeko/zugferd](https://github.com/horstoeko/zugferd) or `easybill/xrechnung-php`'s reader pieces.

## References

- [UNTDID 1001 code list](https://service.unece.org/trade/untdid/d18b/tred/tred1001.htm) - official maintained list
- [XRechnung 3.0 specification](https://xeinkauf.de/xrechnung/) - which codes are mandatory under what circumstances
- [PEPPOL BIS Billing 3.0 codes](https://docs.peppol.eu/poacc/billing/3.0/codelist/UNCL1001-inv/) - the broader European context
