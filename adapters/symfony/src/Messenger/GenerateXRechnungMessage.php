<?php

declare(strict_types=1);

namespace XrechnungKit\Symfony\Messenger;

use XrechnungKit\Mapping\MappingData;

/**
 * Symfony Messenger envelope for asynchronous XRechnung generation. Dispatch
 * with a fully constructed MappingData and a target file path; the matching
 * GenerateXRechnungHandler runs the full pipeline on the configured transport.
 *
 * The MappingData VO graph is serialisable through Symfony's serializer
 * because every kit VO is a readonly value object with no framework
 * references.
 */
final class GenerateXRechnungMessage
{
    public function __construct(
        public readonly MappingData $mapping,
        public readonly string $targetPath,
    ) {
    }
}
