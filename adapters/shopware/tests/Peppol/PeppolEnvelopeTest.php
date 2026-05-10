<?php

declare(strict_types=1);

namespace Vineethkrishnan\XrechnungKitShopware\Tests\Peppol;

use PHPUnit\Framework\TestCase;
use Vineethkrishnan\XrechnungKitShopware\Peppol\PeppolEnvelope;

final class PeppolEnvelopeTest extends TestCase
{
    public function testDefaultsMatchEn16931PeppolBis30(): void
    {
        $envelope = new PeppolEnvelope(
            senderId: '0204:DE123456789',
            recipientId: '0204:04011000-12345-67',
        );

        self::assertSame(PeppolEnvelope::DEFAULT_DOCUMENT_TYPE, $envelope->documentType);
        self::assertSame(PeppolEnvelope::DEFAULT_PROCESS, $envelope->process);
        self::assertNull($envelope->transmissionId);
    }

    public function testToArrayProducesAllRoutingFields(): void
    {
        $envelope = new PeppolEnvelope(
            senderId: '0204:DE123456789',
            recipientId: '0204:04011000-12345-67',
            documentType: 'urn:custom:doctype',
            process: 'urn:custom:process',
            transmissionId: 'tx-42',
        );

        self::assertSame([
            'senderId' => '0204:DE123456789',
            'recipientId' => '0204:04011000-12345-67',
            'documentType' => 'urn:custom:doctype',
            'process' => 'urn:custom:process',
            'transmissionId' => 'tx-42',
        ], $envelope->toArray());
    }
}
