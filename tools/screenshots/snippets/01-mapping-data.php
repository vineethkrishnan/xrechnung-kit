<?php

use XrechnungKit\Mapping\{
    MappingData, DocumentMeta, DocumentTotals,
    LineItem, Money, Party, Address, TaxBreakdown,
    PaymentMeans, TaxId, Contact,
};
use XrechnungKit\XRechnungInvoiceTypeCode;
use XrechnungKit\XRechnungTaxCategory;

$mapping = MappingData::standardInvoice(
    meta: new DocumentMeta(
        invoiceNumber: 'Demo-Invoice-001',
        type:          XRechnungInvoiceTypeCode::COMMERCIAL_INVOICE,
        issueDate:     new DateTimeImmutable('2026-05-09'),
        currency:      'EUR',
        dueDate:       new DateTimeImmutable('2026-06-08'),
        buyerReference:'04011000-12345-67',
    ),
    seller: Party::business(
        name: 'Beispiel Lieferant GmbH',
        address: new Address('Lieferantenstrasse 1', 'Berlin', '10115', 'DE'),
        taxId:   TaxId::vatId('DE123456789'),
        endpointEmail: 'billing@example-supplier.de',
    ),
    buyer: Party::publicAdministration(
        name: 'Bundesamt fuer Beispielzwecke',
        address: new Address('Behoerdenweg 7', 'Bonn', '53113', 'DE'),
        leitwegId: '04011000-12345-67',
        endpointEmail: 'einkauf@example-buyer.de',
    ),
    lines: [
        new LineItem(
            id: '1', description: 'Senior consulting hour, May 2026',
            quantity: '24', unitCode: 'HUR',
            unitPrice: Money::eur('150.00'),
            lineTotal: Money::eur('3600.00'),
            taxCategory: XRechnungTaxCategory::STANDARD_RATE,
            taxPercent: '19.00',
            name: 'Beratungsstunde Senior',
        ),
    ],
    taxes: [
        new TaxBreakdown(
            category: XRechnungTaxCategory::STANDARD_RATE,
            percent:  '19.00',
            taxableAmount: Money::eur('3600.00'),
            taxAmount:     Money::eur('684.00'),
        ),
    ],
    payment: [
        PaymentMeans::sepaCreditTransfer(
            iban: 'DE12500105170648489890',
            bic:  'INGDDEFFXXX',
            accountName: 'Beispiel Lieferant GmbH',
            paymentReference: 'Demo-Invoice-001',
        ),
    ],
    totals: new DocumentTotals(
        lineNet:       Money::eur('3600.00'),
        taxableAmount: Money::eur('3600.00'),
        taxAmount:     Money::eur('684.00'),
        payable:       Money::eur('4284.00'),
    ),
);
