<?php

declare(strict_types=1);

namespace XrechnungKit\Pdfa;

/**
 * Outcome of a single PDF/A-3 embedding pass.
 *
 * Carries the path to the produced file, its size in bytes, and a
 * structured warning list so callers can audit the conformance of
 * the pipeline (for example: "PDF/A profile downgraded from A to B
 * because the source PDF lacks structure tags").
 */
final class EmbeddingResult
{
    /**
     * @param array<int, string> $warnings
     */
    public function __construct(
        public readonly string $outputPath,
        public readonly int $sizeBytes,
        public readonly bool $xmpMetadataApplied,
        public readonly array $warnings = [],
    ) {
    }

    public function hasWarnings(): bool
    {
        return $this->warnings !== [];
    }
}
