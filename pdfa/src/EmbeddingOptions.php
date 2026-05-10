<?php

declare(strict_types=1);

namespace XrechnungKit\Pdfa;

/**
 * Options that drive a single PDF/A-3 embedding pass.
 *
 * Defaults follow the XRechnung 3.0 / EN 16931 PDF/A-3 hybrid
 * convention: attachment named xrechnung.xml, AFRelationship of
 * "Source" (the XML is the canonical truth, the PDF is the visual
 * representation), conformance level B.
 *
 * Conformance level A (accessible) is supported by some producers but
 * has stricter structure-tag requirements that not every base PDF
 * meets; B is the safer default for invoices coming out of templates.
 */
final class EmbeddingOptions
{
    public const ATTACHMENT_NAME = 'xrechnung.xml';
    public const RELATIONSHIP_SOURCE = 'Source';
    public const RELATIONSHIP_DATA = 'Data';
    public const RELATIONSHIP_ALTERNATIVE = 'Alternative';
    public const CONFORMANCE_LEVEL_A = 'A';
    public const CONFORMANCE_LEVEL_B = 'B';

    public function __construct(
        public readonly string $outputPath,
        public readonly string $attachmentName = self::ATTACHMENT_NAME,
        public readonly string $afRelationship = self::RELATIONSHIP_SOURCE,
        public readonly string $conformanceLevel = self::CONFORMANCE_LEVEL_B,
        public readonly ?string $producer = null,
        public readonly ?string $creator = null,
        public readonly ?string $title = null,
        public readonly ?string $subject = null,
        public readonly bool $overwrite = false,
    ) {
        if (!in_array($afRelationship, [self::RELATIONSHIP_SOURCE, self::RELATIONSHIP_DATA, self::RELATIONSHIP_ALTERNATIVE], true)) {
            throw new \InvalidArgumentException(sprintf(
                'AFRelationship must be Source, Data, or Alternative; got %s',
                $afRelationship,
            ));
        }
        if (!in_array($conformanceLevel, [self::CONFORMANCE_LEVEL_A, self::CONFORMANCE_LEVEL_B], true)) {
            throw new \InvalidArgumentException(sprintf(
                'PDF/A-3 conformance level must be A or B; got %s',
                $conformanceLevel,
            ));
        }
    }
}
