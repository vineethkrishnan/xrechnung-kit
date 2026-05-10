<?php

declare(strict_types=1);

namespace Vineethkrishnan\XrechnungKitShopware\Peppol;

/**
 * Pluggable PEPPOL Access Point delivery contract.
 *
 * One implementation ships out of the box (WebhookPeppolDelivery), which
 * POSTs the XRechnung XML and the envelope JSON to a configurable URL -
 * suited to operators whose own AP relay or middleware service speaks
 * PEPPOL on their behalf.
 *
 * To integrate with a commercial AP (Storecove, B2B Router, Pagero,
 * etc.), provide a project-local implementation, register it with the
 * xrechnung_kit_shopware.peppol_delivery service tag, and prioritise
 * it via PluginConfig (a future config knob). The kit deliberately does
 * not pick a default commercial provider so the plugin stays
 * vendor-neutral.
 */
interface PeppolDeliveryInterface
{
    /**
     * Stable identifier (e.g., "webhook", "storecove", "b2brouter")
     * used by config and logging to indicate which provider handled
     * the transmission.
     */
    public function name(): string;

    /**
     * Returns true when this implementation is configured well enough
     * to attempt delivery. Used by PeppolDeliveryService to skip
     * implementations that have no configured endpoint or credentials.
     */
    public function isConfigured(?string $salesChannelId): bool;

    /**
     * Deliver the XRechnung XML at $xmlPath under the given envelope.
     * Implementations must not throw on transport errors - return a
     * PeppolDeliveryResult::failed() instead. Throwing is reserved for
     * programmer errors (e.g., the file does not exist).
     */
    public function deliver(PeppolEnvelope $envelope, string $xmlPath, ?string $salesChannelId = null): PeppolDeliveryResult;
}
