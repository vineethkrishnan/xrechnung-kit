<?php

declare(strict_types=1);

namespace Vineethkrishnan\XrechnungKitTypo3\Service;

use Psr\Log\LoggerInterface;
use XrechnungKit\Builder\XRechnungBuilder;
use XrechnungKit\Mapping\MappingData;
use XrechnungKit\XRechnungGenerator;
use XrechnungKit\XRechnungValidator;

/**
 * Single TYPO3-side entry point that exposes the core pipeline as a typed
 * service. Inject this into controllers, scheduler tasks, or hooks.
 *
 * Scaffolding only at this stage; the surface intentionally mirrors what
 * the Laravel and Symfony adapters expose so consumers writing TYPO3
 * extensions today can read the public docs and predict how it will land.
 */
final class XrechnungService
{
    public function __construct(
        private readonly XRechnungValidator $validator,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function generateAndValidate(MappingData $mapping, string $targetPath): string
    {
        $entity = XRechnungBuilder::buildEntity($mapping);
        $generator = new XRechnungGenerator($entity);
        $finalPath = $generator->generateXRechnung($targetPath);

        if (!$this->validator->validate($finalPath)) {
            $this->logger->warning('xrechnung-kit-typo3: file landed at *_invalid.xml', [
                'path' => $finalPath,
                'errors' => $this->validator->getErrors(),
            ]);
        }

        return $finalPath;
    }
}
