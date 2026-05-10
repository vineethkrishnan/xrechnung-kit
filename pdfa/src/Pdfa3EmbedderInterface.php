<?php

declare(strict_types=1);

namespace XrechnungKit\Pdfa;

/**
 * Pluggable PDF/A-3 hybrid invoice producer.
 *
 * Two methods on purpose:
 *
 *   embed()           takes an existing PDF and a generated XRechnung
 *                     XML and produces a PDF/A-3 file with the XML
 *                     embedded as an attachment plus the XMP metadata
 *                     that PDF/A-3 readers (and ZUGFeRD-style consumers)
 *                     look for.
 *
 *   renderAndEmbed()  for callers who do not already have a base PDF -
 *                     accepts an HTML string or a template id and
 *                     produces both the visual PDF and the embed in
 *                     one pass.
 *
 * Implementations must NOT throw on transport-style errors; they
 * persist warnings into EmbeddingResult::warnings instead. Throwing
 * is reserved for programmer errors (input file does not exist,
 * options invalid, output directory not writable).
 *
 * Reference implementations:
 *   - mpdf/mpdf >= 8.2 (recommended)
 *   - setasign/fpdi >= 2.5 with PDF/A licensing
 *   - any custom impl your project already uses
 *
 * The kit deliberately ships only the interface plus a stub: PDF
 * library choice is opinionated and rotates over time, so projects
 * pick their own producer.
 */
interface Pdfa3EmbedderInterface
{
    /**
     * Stable identifier (e.g., "mpdf", "fpdi", "stub") used by config
     * and logging to indicate which producer handled the conversion.
     */
    public function name(): string;

    /**
     * Returns true when the underlying PDF library is reachable and
     * licensed (where applicable) at this moment. Used by service
     * registries to skip implementations whose dependencies are not
     * present.
     */
    public function isAvailable(): bool;

    /**
     * Embed an existing XRechnung XML inside an existing visual PDF
     * and write the resulting PDF/A-3 to the configured output path.
     *
     * @throws \RuntimeException when an input is missing or the
     *         underlying producer cannot proceed
     */
    public function embed(
        string $basePdfPath,
        string $xrechnungXmlPath,
        EmbeddingOptions $options,
    ): EmbeddingResult;

    /**
     * Render a visual PDF from the given HTML or template id and
     * embed the XRechnung XML inside in one pass.
     *
     * @throws \RuntimeException when the producer cannot proceed
     */
    public function renderAndEmbed(
        string $htmlOrTemplate,
        string $xrechnungXmlPath,
        EmbeddingOptions $options,
    ): EmbeddingResult;
}
