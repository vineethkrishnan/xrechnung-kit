# Glossary of German XRechnung terms

XRechnung is a German federal e-invoicing standard. Most of its terminology is in German. This page maps the German terms to xrechnung-kit concepts and to the EN 16931 BT/BG codes they implement.

| German term | English meaning | xrechnung-kit concept | EN 16931 |
|---|---|---|---|
| Anzahlung | Down payment / advance payment | `MappingData::partialInvoice(...)`, document type `PARTIAL_326` | UNTDID 1001 = 326 |
| Anzahlungsstornierung | Deposit cancellation | `MappingData::depositCancellation(...)` | UNTDID 1001 = 381 + BG-3 |
| Behoerde | Public authority / agency | The buyer party in B2G invoices; requires Leitweg-ID | n/a |
| Bestellnummer | Buyer order reference | `DocumentMeta::buyerReference` | BT-13 |
| Faelligkeitsdatum | Due date | `DocumentMeta::dueDate` | BT-9 |
| Gutschrift | Credit note | `MappingData::creditNote(...)`, document type `CREDIT_NOTE_381` | UNTDID 1001 = 381 |
| Kaution | Security deposit / caution | `MappingData::cautionInvoice(...)` | UNTDID 1001 = 380 (caution variant) |
| Kreditor | Creditor (the seller in payment context) | `Party seller` (in payment-means context) | BG-4 |
| Leistungszeitraum | Period of performance | `DocumentPeriod` on `MappingData`, or `LineItem::period` | BG-14, BG-26 |
| Leitweg-ID | Routing identifier for German federal recipients | `Party buyer.leitwegId` | BT-10 |
| Rechnung | Invoice | `MappingData::standardInvoice(...)`, document type `INVOICE_380` | UNTDID 1001 = 380 |
| Rechnungsempfaenger | Invoice recipient (the buyer) | `Party buyer` | BG-7 |
| Rechnungssteller | Invoice issuer (the seller) | `Party seller` | BG-4 |
| Rechnungsnummer | Invoice number | `DocumentMeta::number` | BT-1 |
| Sicherungseinbehalt | Security retention | (not v1.0; see future architecture work) | - |
| Skontovereinbarung | Discount-for-prompt-payment terms | `PaymentTerms` (future) | BT-20, BG-20 |
| Steuerkategorie | Tax category | `XRechnungTaxCategory` enum | BG-23 (BT-118) |
| Steuersatz | Tax rate (percentage) | `LineItem::taxPercent`, `TaxBreakdown::percent` | BT-119 |
| Stornorechnung | Cancellation invoice | `MappingData::creditNote(...)` | UNTDID 1001 = 381 |
| Teilrechnung | Partial invoice (advance / interim) | `MappingData::partialInvoice(...)` | UNTDID 1001 = 326 |
| Umsatzsteuer-Identifikationsnummer (USt-IdNr) | VAT identification number | `TaxId::vatId(...)` | BT-31, BT-48 |
| Verwendungszweck | Payment reference text | `PaymentMeans::*` reference field | BT-83 |
| Vorname / Nachname | First name / last name | `Party::contact` (split fields kept for BT-41 / BT-43) | BT-41, BT-43 |
| Zahlungsbedingungen | Payment terms | `PaymentTerms` (future) | BG-20 |
| Zahlungsmittel | Means of payment | `PaymentMeansCode` enum | BG-16, BT-81 |
| ZRE / OZG-RE | The two German federal e-invoice receipt portals | not in scope; consumer transports the XML | - |

For the canonical definitions, see KoSIT's [XRechnung specification](https://www.xoev.de/xrechnung) (German). EN 16931 itself is paywalled via CEN.
