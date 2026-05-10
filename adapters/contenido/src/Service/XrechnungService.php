<?php

declare(strict_types=1);

namespace Vineethkrishnan\XrechnungKitContenido\Service;

use Psr\Log\LoggerInterface;
use XrechnungKit\Builder\XRechnungBuilder;
use XrechnungKit\Mapping\MappingData;
use XrechnungKit\XRechnungGenerator;
use XrechnungKit\XRechnungValidator;

/**
 * Contenido-side entry point. Receives a typed MappingData (built by a
 * Contenido-domain mapper that lives in the consumer's plugin or theme)
 * and runs the full core pipeline.
 *
 * Contenido does not have a standard service container in the way TYPO3
 * or Shopware do, so this is a plain-old PHP class. Instantiate it
 * wherever the consumer needs it:
 *
 *   $service = new XrechnungService(new XRechnungValidator(), $cfg['log']);
 *   $path    = $service->generateAndValidate($mapping, $target);
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
            $this->logger->warning('xrechnung-kit-contenido: file landed at *_invalid.xml', [
                'path' => $finalPath,
                'errors' => $this->validator->getErrors(),
            ]);
        }

        return $finalPath;
    }
}
