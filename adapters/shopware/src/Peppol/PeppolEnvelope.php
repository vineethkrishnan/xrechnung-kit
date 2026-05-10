<?php

declare(strict_types=1);

namespace Vineethkrishnan\XrechnungKitShopware\Peppol;

/**
 * Routing envelope passed to a PeppolDeliveryInterface implementation.
 *
 * The envelope is intentionally minimal: it carries sender / recipient
 * PEPPOL participant identifiers, the document type identifier, and a
 * transmission id the AP provider can echo back for correlation. The
 * heavy lifting (building the actual SBDH, signing, AS2 / AS4 transport)
 * is the AP provider's job; we describe what we want sent.
 */
final class PeppolEnvelope
{
    public const DEFAULT_DOCUMENT_TYPE = 'urn:cen.eu:en16931:2017#compliant#urn:fdc:peppol.eu:2017:poacc:billing:3.0';
    public const DEFAULT_PROCESS = 'urn:fdc:peppol.eu:2017:poacc:billing:01:1.0';

    public function __construct(
        public readonly string $senderId,
        public readonly string $recipientId,
        public readonly string $documentType = self::DEFAULT_DOCUMENT_TYPE,
        public readonly string $process = self::DEFAULT_PROCESS,
        public readonly ?string $transmissionId = null,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'senderId' => $this->senderId,
            'recipientId' => $this->recipientId,
            'documentType' => $this->documentType,
            'process' => $this->process,
            'transmissionId' => $this->transmissionId,
        ];
    }
}
