<?php

declare(strict_types=1);

namespace XrechnungKit\Pdfa\Tests;

use PHPUnit\Framework\TestCase;
use XrechnungKit\Pdfa\EmbeddingOptions;

final class EmbeddingOptionsTest extends TestCase
{
    public function testDefaultsMatchTheXrechnungHybridConvention(): void
    {
        $options = new EmbeddingOptions(outputPath: '/tmp/out.pdf');

        self::assertSame('/tmp/out.pdf', $options->outputPath);
        self::assertSame('xrechnung.xml', $options->attachmentName);
        self::assertSame('Source', $options->afRelationship);
        self::assertSame('B', $options->conformanceLevel);
        self::assertFalse($options->overwrite);
    }

    public function testRejectsUnknownAfRelationship(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('AFRelationship must be Source, Data, or Alternative');

        new EmbeddingOptions(outputPath: '/tmp/out.pdf', afRelationship: 'Unsupervised');
    }

    public function testRejectsUnknownConformanceLevel(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('PDF/A-3 conformance level must be A or B');

        new EmbeddingOptions(outputPath: '/tmp/out.pdf', conformanceLevel: 'U');
    }
}
