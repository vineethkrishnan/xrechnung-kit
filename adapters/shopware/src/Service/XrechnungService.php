<?php

declare(strict_types=1);

namespace Vineethkrishnan\XrechnungKitShopware\Service;

use Psr\Log\LoggerInterface;
use XrechnungKit\Builder\XRechnungBuilder;
use XrechnungKit\Mapping\MappingData;
use XrechnungKit\XRechnungGenerator;
use XrechnungKit\XRechnungValidator;

/**
 * Shopware-side entry point. Receives a typed MappingData (built by a
 * Shopware-domain mapper that lives in the consumer's project for now)
 * and runs the full core pipeline.
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
            $this->logger->warning('xrechnung-kit-shopware: file landed at *_invalid.xml', [
                'path' => $finalPath,
                'errors' => $this->validator->getErrors(),
            ]);
        }

        return $finalPath;
    }
}
