<?php

declare(strict_types=1);

namespace XrechnungKit\Pdfa\Tests\Stub;

use PHPUnit\Framework\TestCase;
use XrechnungKit\Pdfa\EmbeddingOptions;
use XrechnungKit\Pdfa\Stub\StubPdfa3Embedder;

final class StubPdfa3EmbedderTest extends TestCase
{
    public function testIsAvailableFalseSoServiceRegistriesSkipIt(): void
    {
        self::assertFalse((new StubPdfa3Embedder())->isAvailable());
    }

    public function testNameReturnsStub(): void
    {
        self::assertSame('stub', (new StubPdfa3Embedder())->name());
    }

    public function testEmbedThrowsWithGuidancePointer(): void
    {
        $stub = new StubPdfa3Embedder();
        $options = new EmbeddingOptions(outputPath: '/tmp/out.pdf');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('placeholder');

        $stub->embed('/tmp/in.pdf', '/tmp/in.xml', $options);
    }

    public function testRenderAndEmbedThrowsWithGuidancePointer(): void
    {
        $stub = new StubPdfa3Embedder();
        $options = new EmbeddingOptions(outputPath: '/tmp/out.pdf');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('embedding-pdfa');

        $stub->renderAndEmbed('<html/>', '/tmp/in.xml', $options);
    }
}
