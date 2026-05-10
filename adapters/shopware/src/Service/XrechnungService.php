<?php

declare(strict_types=1);

namespace Vineethkrishnan\XrechnungKitShopware\Service;

use Psr\Log\LoggerInterface;
use Vineethkrishnan\XrechnungKitShopware\Core\Content\XrechnungInvoice\XrechnungInvoiceDefinition;
use XrechnungKit\Builder\XRechnungBuilder;
use XrechnungKit\Mapping\MappingData;
use XrechnungKit\XRechnungGenerator;
use XrechnungKit\XRechnungValidator;

/**
 * Shopware-side orchestrator over the framework-agnostic core.
 *
 * Receives a typed MappingData (built by OrderToMappingData) and a
 * target file path, runs the full builder -> generator -> validator
 * pipeline, optionally runs the KoSIT Schematron pass, and returns a
 * structured XrechnungGenerationResult that the order-state
 * subscriber persists into the xrechnung_kit_invoice table.
 *
 * Persistence is intentionally not done here - this service stays a
 * pure glue over the core pipeline so it stays easy to unit-test.
 */
final class XrechnungService
{
    private const VALIDATOR_VERSION = '2.0';

    public function __construct(
        private readonly XRechnungValidator $validator,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function generate(
        MappingData $mapping,
        string $targetPath,
        bool $runKosit = false,
    ): XrechnungGenerationResult {
        $entity = XRechnungBuilder::buildEntity($mapping);
        $generator = new XRechnungGenerator($entity);
        $finalPath = $generator->generateXRechnung($targetPath);

        $xsdPassed = $this->validator->validate($finalPath);
        $errors = $this->validator->getErrors();

        if (!$xsdPassed) {
            $this->logger->warning('xrechnung-kit-shopware: file landed at *_invalid.xml', [
                'path' => $finalPath,
                'errors' => $errors,
            ]);
        }

        $kositResult = XrechnungInvoiceDefinition::KOSIT_SKIPPED;
        if ($xsdPassed && $runKosit) {
            $kositPassed = $this->runSchematron($finalPath);
            $kositResult = $kositPassed
                ? XrechnungInvoiceDefinition::KOSIT_PASS
                : XrechnungInvoiceDefinition::KOSIT_FAIL;
            if (!$kositPassed) {
                $errors = array_merge($errors, $this->validator->getErrors());
            }
        }

        $status = $xsdPassed
            ? XrechnungInvoiceDefinition::STATUS_GENERATED
            : XrechnungInvoiceDefinition::STATUS_INVALID;

        return new XrechnungGenerationResult(
            path: $finalPath,
            status: $status,
            errors: array_values(array_unique($errors)),
            generatedAt: new \DateTimeImmutable(),
            validatorVersion: self::VALIDATOR_VERSION,
            kositResult: $kositResult,
        );
    }

    /**
     * Backward-compatible wrapper for callers that only care about the
     * final path. New callers should use generate() directly.
     */
    public function generateAndValidate(MappingData $mapping, string $targetPath): string
    {
        return $this->generate($mapping, $targetPath)->path;
    }

    private function runSchematron(string $path): bool
    {
        if (!method_exists($this->validator, 'validateSchematron')) {
            $this->logger->info(
                'xrechnung-kit-shopware: KoSIT requested but kosit-bundle is not installed; skipping',
                ['path' => $path],
            );
            return false;
        }
        return (bool) $this->validator->validateSchematron($path);
    }
}
