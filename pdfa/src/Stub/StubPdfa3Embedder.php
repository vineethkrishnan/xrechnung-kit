<?php

declare(strict_types=1);

namespace XrechnungKit\Pdfa\Stub;

use XrechnungKit\Pdfa\EmbeddingOptions;
use XrechnungKit\Pdfa\EmbeddingResult;
use XrechnungKit\Pdfa\Pdfa3EmbedderInterface;

/**
 * Default no-op implementation that documents the expected behaviour
 * without actually producing PDFs. Throws \RuntimeException with a
 * pointer to the guidance docs so consumers do not silently accept
 * a stub in production.
 *
 * Replace with a real producer (mpdf-based, fpdi-based, or your
 * project's own) by binding your implementation to
 * Pdfa3EmbedderInterface in your container.
 */
final class StubPdfa3Embedder implements Pdfa3EmbedderInterface
{
    public function name(): string
    {
        return 'stub';
    }

    public function isAvailable(): bool
    {
        return false;
    }

    public function embed(
        string $basePdfPath,
        string $xrechnungXmlPath,
        EmbeddingOptions $options,
    ): EmbeddingResult {
        throw $this->notImplemented(__FUNCTION__);
    }

    public function renderAndEmbed(
        string $htmlOrTemplate,
        string $xrechnungXmlPath,
        EmbeddingOptions $options,
    ): EmbeddingResult {
        throw $this->notImplemented(__FUNCTION__);
    }

    private function notImplemented(string $method): \RuntimeException
    {
        return new \RuntimeException(sprintf(
            'StubPdfa3Embedder::%s() is a placeholder. Bind a concrete '
            . 'Pdfa3EmbedderInterface implementation (mpdf-based or '
            . 'fpdi-based) in your container. See '
            . 'https://xrechnung-kit.vineethnk.in/embedding-pdfa for examples.',
            $method,
        ));
    }
}
