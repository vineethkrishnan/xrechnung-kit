<?php

declare(strict_types=1);

namespace Vineethkrishnan\XrechnungKitShopware\Service;

use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTax;
use Shopware\Core\Checkout\Order\Aggregate\OrderAddress\OrderAddressEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use XrechnungKit\Mapping\Address;
use XrechnungKit\Mapping\Contact;
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
 * Walks a fully-loaded Shopware OrderEntity and produces a typed
 * MappingData ready for the core pipeline.
 *
 * Required associations on the OrderEntity:
 *  - lineItems
 *  - billingAddress.country
 *  - orderCustomer
 *  - currency
 *  - price (with calculatedTaxes)
 *
 * Document-class selection in Phase B: only standardInvoice (UNTDID
 * 380). Partial / credit-note / deposit-cancellation classes will
 * land in a follow-up that ties to order-document-type custom fields.
 *
 * Tax-mode assumption in Phase B: net-tax orders (the typical B2B
 * setup). Gross-mode orders may need additional rounding handling;
 * tracked separately.
 */
final class OrderToMappingData
{
    private const DEFAULT_UNIT_CODE = 'C62';
    private const DEFAULT_DUE_DATE_OFFSET_DAYS = 30;

    public function __construct(
        private readonly PluginConfig $config,
    ) {
    }

    public function fromOrder(OrderEntity $order): MappingData
    {
        $salesChannelId = $order->getSalesChannelId();
        $currency = $order->getCurrency()?->getIsoCode();
        if ($currency === null || $currency === '') {
            throw new \DomainException(sprintf(
                'Order %s has no currency association loaded.',
                $order->getOrderNumber() ?? $order->getId(),
            ));
        }

        $billing = $order->getBillingAddress();
        if (!$billing instanceof OrderAddressEntity) {
            throw new \DomainException(sprintf(
                'Order %s has no billing address loaded.',
                $order->getOrderNumber() ?? $order->getId(),
            ));
        }

        $leitwegId = $this->resolveLeitwegId($order, $salesChannelId);
        $isB2g = $leitwegId !== null && $leitwegId !== '';

        if (!$isB2g && $this->config->requireLeitwegIdForB2g($salesChannelId) === false) {
            // B2C / B2B path - acceptable when the strict B2G gate is off.
        }

        return MappingData::standardInvoice(
            meta: $this->buildMeta($order, $leitwegId, $currency),
            seller: $this->buildSeller($salesChannelId),
            buyer: $this->buildBuyer($order, $billing, $leitwegId),
            lines: $this->buildLines($order, $currency),
            taxes: $this->buildTaxBreakdowns($order, $currency),
            payment: $this->buildPaymentMeans($order, $salesChannelId),
            totals: $this->buildTotals($order, $currency),
        );
    }

    private function buildMeta(OrderEntity $order, ?string $leitwegId, string $currency): DocumentMeta
    {
        $invoiceNumber = $order->getOrderNumber();
        if ($invoiceNumber === null || $invoiceNumber === '') {
            throw new \DomainException(sprintf(
                'Order %s has no orderNumber.',
                $order->getId(),
            ));
        }

        $issueDate = $order->getOrderDateTime();
        $issue = $issueDate instanceof \DateTimeInterface
            ? \DateTimeImmutable::createFromInterface($issueDate)
            : new \DateTimeImmutable();

        return new DocumentMeta(
            invoiceNumber: $invoiceNumber,
            type: XRechnungInvoiceTypeCode::COMMERCIAL_INVOICE,
            issueDate: $issue,
            currency: $currency,
            dueDate: $issue->modify(sprintf('+%d days', self::DEFAULT_DUE_DATE_OFFSET_DAYS)),
            buyerReference: $leitwegId,
            note: null,
        );
    }

    private function buildSeller(?string $salesChannelId): Party
    {
        $vatId = $this->config->getSellerVatId($salesChannelId);
        if ($vatId === '') {
            throw new \DomainException('Seller VAT ID is not configured for this sales channel.');
        }

        $address = new Address(
            street: $this->require('sellerStreet', $this->config->getSellerStreet($salesChannelId)),
            city: $this->require('sellerCity', $this->config->getSellerCity($salesChannelId)),
            zip: $this->require('sellerPostalCode', $this->config->getSellerPostalCode($salesChannelId)),
            countryCode: $this->config->getSellerCountryCode($salesChannelId),
        );

        return Party::business(
            name: $this->require('sellerName', $this->config->getSellerName($salesChannelId)),
            address: $address,
            taxId: TaxId::vatId($vatId),
            endpointEmail: $this->config->getSellerEndpointEmail($salesChannelId),
        );
    }

    private function buildBuyer(OrderEntity $order, OrderAddressEntity $billing, ?string $leitwegId): Party
    {
        $name = trim(sprintf(
            '%s %s %s',
            $billing->getCompany() ?? '',
            $billing->getFirstName() ?? '',
            $billing->getLastName() ?? '',
        ));
        if ($name === '') {
            $name = $order->getOrderCustomer()?->getEmail() ?? 'Unknown buyer';
        }

        $countryCode = $billing->getCountry()?->getIso();
        if ($countryCode === null || $countryCode === '') {
            throw new \DomainException(sprintf(
                'Order %s billing address has no country iso loaded.',
                $order->getOrderNumber(),
            ));
        }

        $address = new Address(
            street: $this->require('billingStreet', (string) $billing->getStreet()),
            city: $this->require('billingCity', (string) $billing->getCity()),
            zip: $this->require('billingZip', (string) $billing->getZipcode()),
            countryCode: $countryCode,
        );

        $contact = new Contact(
            name: trim(sprintf('%s %s', $billing->getFirstName() ?? '', $billing->getLastName() ?? '')) ?: null,
            phone: $billing->getPhoneNumber() ?: null,
            email: $order->getOrderCustomer()?->getEmail() ?: null,
        );
        $endpointEmail = $order->getOrderCustomer()?->getEmail();

        if ($leitwegId !== null && $leitwegId !== '') {
            return Party::publicAdministration(
                name: $name,
                address: $address,
                leitwegId: $leitwegId,
                contact: $contact,
                endpointEmail: $endpointEmail,
            );
        }

        $buyerVatId = $billing->getVatId();
        $buyerTaxId = ($buyerVatId !== null && $buyerVatId !== '')
            ? TaxId::vatId($buyerVatId)
            : null;

        return Party::business(
            name: $name,
            address: $address,
            taxId: $buyerTaxId,
            contact: $contact,
            endpointEmail: $endpointEmail,
        );
    }

    /**
     * @return array<int, LineItem>
     */
    private function buildLines(OrderEntity $order, string $currency): array
    {
        $items = $order->getLineItems();
        if ($items === null || $items->count() === 0) {
            throw new \DomainException(sprintf(
                'Order %s has no line items loaded.',
                $order->getOrderNumber(),
            ));
        }

        $lines = [];
        $sequence = 0;
        foreach ($items as $line) {
            if (!$line instanceof OrderLineItemEntity) {
                continue;
            }
            ++$sequence;
            $lines[] = $this->buildLine($line, $sequence, $currency);
        }
        return $lines;
    }

    private function buildLine(OrderLineItemEntity $line, int $sequence, string $currency): LineItem
    {
        $price = $line->getPrice();
        $taxes = $price?->getCalculatedTaxes();
        $firstTax = $taxes?->first();
        $taxRate = $firstTax instanceof CalculatedTax ? $firstTax->getTaxRate() : 0.0;

        return new LineItem(
            id: (string) $sequence,
            description: $line->getLabel() ?? sprintf('Line item %d', $sequence),
            quantity: $this->formatQuantity((float) $line->getQuantity()),
            unitCode: self::DEFAULT_UNIT_CODE,
            unitPrice: $this->money((float) $line->getUnitPrice(), $currency),
            lineTotal: $this->money((float) $line->getTotalPrice(), $currency),
            taxCategory: $this->mapTaxCategory($taxRate),
            taxPercent: $this->formatRate($taxRate),
            name: $line->getLabel(),
        );
    }

    /**
     * @return array<int, TaxBreakdown>
     */
    private function buildTaxBreakdowns(OrderEntity $order, string $currency): array
    {
        $price = $order->getPrice();
        $taxes = $price?->getCalculatedTaxes();
        if ($taxes === null || $taxes->count() === 0) {
            throw new \DomainException(sprintf(
                'Order %s carries no calculated taxes.',
                $order->getOrderNumber(),
            ));
        }

        $breakdowns = [];
        foreach ($taxes as $tax) {
            if (!$tax instanceof CalculatedTax) {
                continue;
            }
            $breakdowns[] = new TaxBreakdown(
                category: $this->mapTaxCategory($tax->getTaxRate()),
                percent: $this->formatRate($tax->getTaxRate()),
                taxableAmount: $this->money($tax->getPrice(), $currency),
                taxAmount: $this->money($tax->getTax(), $currency),
            );
        }
        return $breakdowns;
    }

    /**
     * @return array<int, PaymentMeans>
     */
    private function buildPaymentMeans(OrderEntity $order, ?string $salesChannelId): array
    {
        $iban = $this->config->getSellerIban($salesChannelId);
        $bic = $this->config->getSellerBic($salesChannelId);
        $accountName = $this->config->getSellerAccountName($salesChannelId)
            ?? $this->config->getSellerName($salesChannelId);
        $reference = $order->getOrderNumber() ?? $order->getId();

        if ($iban === null || $iban === '') {
            return [];
        }

        return [
            PaymentMeans::sepaCreditTransfer(
                iban: $iban,
                bic: $bic ?? '',
                accountName: $accountName,
                paymentReference: $reference,
            ),
        ];
    }

    private function buildTotals(OrderEntity $order, string $currency): DocumentTotals
    {
        $price = $order->getPrice();
        if ($price === null) {
            throw new \DomainException(sprintf(
                'Order %s has no calculated price.',
                $order->getOrderNumber(),
            ));
        }

        $net = $order->getAmountNet();
        $gross = $order->getAmountTotal();
        $tax = $gross - $net;

        return new DocumentTotals(
            lineNet: $this->money($net, $currency),
            taxableAmount: $this->money($net, $currency),
            taxAmount: $this->money($tax, $currency),
            payable: $this->money($gross, $currency),
        );
    }

    private function resolveLeitwegId(OrderEntity $order, ?string $salesChannelId): ?string
    {
        $source = $this->config->getLeitwegIdSource($salesChannelId);

        $candidate = match ($source) {
            PluginConfig::LEITWEG_FROM_ORDER_FIELD => $order->getCustomFields()['xrechnungKitLeitwegId'] ?? null,
            PluginConfig::LEITWEG_FROM_CUSTOMER_FIELD => $order->getOrderCustomer()?->getCustomer()?->getCustomFields()['xrechnungKitLeitwegId'] ?? null,
            PluginConfig::LEITWEG_FROM_BILLING_COMPANY => $order->getBillingAddress()?->getCompany() ?? null,
            default => null,
        };

        if (!is_string($candidate)) {
            return null;
        }
        $trimmed = trim($candidate);
        return $trimmed === '' ? null : $trimmed;
    }

    private function mapTaxCategory(float $rate): XRechnungTaxCategory
    {
        if ($rate <= 0.0) {
            return XRechnungTaxCategory::ZERO_RATED_GOODS;
        }
        if ($rate >= 18.0) {
            return XRechnungTaxCategory::STANDARD_RATE;
        }
        if ($rate >= 5.0) {
            return XRechnungTaxCategory::REDUCED_RATE;
        }
        return XRechnungTaxCategory::STANDARD_RATE;
    }

    private function formatRate(float $rate): string
    {
        return number_format($rate, 2, '.', '');
    }

    private function formatQuantity(float $quantity): string
    {
        if (fmod($quantity, 1.0) === 0.0) {
            return (string) (int) $quantity;
        }
        return number_format($quantity, 2, '.', '');
    }

    private function money(float $amount, string $currency): Money
    {
        return new Money(number_format($amount, 2, '.', ''), $currency);
    }

    private function require(string $key, string $value): string
    {
        if ($value === '') {
            throw new \DomainException(sprintf('Required field %s is empty.', $key));
        }
        return $value;
    }
}
