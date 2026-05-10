<?php

declare(strict_types=1);

namespace Vineethkrishnan\XrechnungKitShopware\Tests\Peppol;

use PHPUnit\Framework\TestCase;
use Vineethkrishnan\XrechnungKitShopware\Core\Content\XrechnungInvoice\XrechnungInvoiceDefinition;
use Vineethkrishnan\XrechnungKitShopware\Peppol\PeppolDeliveryResult;

final class PeppolDeliveryResultTest extends TestCase
{
    public function testSentFactoryProducesSentStatusAndCarriesResponse(): void
    {
        $result = PeppolDeliveryResult::sent(['transmissionId' => 'abc-123']);

        self::assertSame(XrechnungInvoiceDefinition::DELIVERY_SENT, $result->status);
        self::assertSame(['transmissionId' => 'abc-123'], $result->response);
        self::assertNull($result->error);
        self::assertInstanceOf(\DateTimeImmutable::class, $result->attemptedAt);
    }

    public function testFailedFactoryClampsErrorMessageToTwoThousandChars(): void
    {
        $longMessage = str_repeat('x', 5000);
        $result = PeppolDeliveryResult::failed($longMessage);

        self::assertSame(XrechnungInvoiceDefinition::DELIVERY_FAILED, $result->status);
        self::assertNotNull($result->error);
        self::assertLessThanOrEqual(2000, strlen($result->error));
    }

    public function testFailedFactoryAcceptsOptionalResponse(): void
    {
        $result = PeppolDeliveryResult::failed('boom', ['status' => 502, 'raw' => 'gateway down']);

        self::assertSame(XrechnungInvoiceDefinition::DELIVERY_FAILED, $result->status);
        self::assertSame(['status' => 502, 'raw' => 'gateway down'], $result->response);
        self::assertSame('boom', $result->error);
    }

    public function testSkippedFactoryStoresReasonAsErrorAndDropsResponse(): void
    {
        $result = PeppolDeliveryResult::skipped('Recipient PEPPOL endpoint missing');

        self::assertSame(XrechnungInvoiceDefinition::DELIVERY_SKIPPED, $result->status);
        self::assertSame('Recipient PEPPOL endpoint missing', $result->error);
        self::assertNull($result->response);
    }
}
