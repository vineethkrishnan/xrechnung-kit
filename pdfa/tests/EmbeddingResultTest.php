<?php

declare(strict_types=1);

namespace XrechnungKit\Pdfa\Tests;

use PHPUnit\Framework\TestCase;
use XrechnungKit\Pdfa\EmbeddingResult;

final class EmbeddingResultTest extends TestCase
{
    public function testStoresPathSizeAndXmpFlag(): void
    {
        $result = new EmbeddingResult(
            outputPath: '/tmp/invoice-001.pdf',
            sizeBytes: 187_433,
            xmpMetadataApplied: true,
        );

        self::assertSame('/tmp/invoice-001.pdf', $result->outputPath);
        self::assertSame(187_433, $result->sizeBytes);
        self::assertTrue($result->xmpMetadataApplied);
        self::assertFalse($result->hasWarnings());
    }

    public function testHasWarningsTrueWhenWarningListIsNonEmpty(): void
    {
        $result = new EmbeddingResult(
            outputPath: '/tmp/out.pdf',
            sizeBytes: 1024,
            xmpMetadataApplied: false,
            warnings: ['Conformance level downgraded from A to B because base PDF lacks structure tags.'],
        );

        self::assertTrue($result->hasWarnings());
        self::assertCount(1, $result->warnings);
    }
}
