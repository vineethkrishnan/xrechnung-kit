<?php

declare(strict_types=1);

namespace Vineethkrishnan\XrechnungKitShopware\Service;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\CustomField\CustomFieldTypes;

/**
 * Installs and removes the xrechnung_kit custom field set on plugin
 * lifecycle events. Two fields land on both customer and order:
 *
 *  - xrechnungKitLeitwegId: the buyer's Leitweg-ID for B2G invoicing.
 *    Required when the buyer is a public-administration entity.
 *  - xrechnungKitPeppolId:  optional PEPPOL Endpoint Identifier used by
 *    the future Tier 3 PEPPOL delivery feature.
 *
 * The field set is identified by name xrechnung_kit so reinstalls are
 * idempotent.
 */
final class CustomFieldSetInstaller
{
    public const SET_NAME = 'xrechnung_kit';

    public function __construct(
        private readonly EntityRepository $customFieldSetRepository,
    ) {
    }

    public function install(Context $context): void
    {
        $existing = $this->findExistingId($context);

        $this->customFieldSetRepository->upsert([
            [
                'id' => $existing ?? Uuid::randomHex(),
                'name' => self::SET_NAME,
                'global' => true,
                'config' => [
                    'label' => [
                        'en-GB' => 'XRechnung',
                        'de-DE' => 'XRechnung',
                    ],
                    'translated' => true,
                ],
                'customFields' => [
                    [
                        'name' => 'xrechnungKitLeitwegId',
                        'type' => CustomFieldTypes::TEXT,
                        'config' => [
                            'label' => [
                                'en-GB' => 'Leitweg-ID (B2G recipient)',
                                'de-DE' => 'Leitweg-ID (B2G-Empfaenger)',
                            ],
                            'helpText' => [
                                'en-GB' => 'Required for invoices to German federal, state, or municipal entities.',
                                'de-DE' => 'Pflichtangabe fuer Rechnungen an Bundes-, Landes- oder kommunale Stellen.',
                            ],
                            'componentName' => 'sw-field',
                            'customFieldType' => 'text',
                            'customFieldPosition' => 1,
                            'validation' => 'pattern',
                        ],
                    ],
                    [
                        'name' => 'xrechnungKitPeppolId',
                        'type' => CustomFieldTypes::TEXT,
                        'config' => [
                            'label' => [
                                'en-GB' => 'PEPPOL Endpoint ID (optional)',
                                'de-DE' => 'PEPPOL-Endpunkt-ID (optional)',
                            ],
                            'helpText' => [
                                'en-GB' => 'Used by the optional PEPPOL delivery feature. Leave blank if not applicable.',
                                'de-DE' => 'Wird vom optionalen PEPPOL-Versand verwendet. Leer lassen, wenn nicht zutreffend.',
                            ],
                            'componentName' => 'sw-field',
                            'customFieldType' => 'text',
                            'customFieldPosition' => 2,
                        ],
                    ],
                ],
                'relations' => [
                    ['entityName' => 'order'],
                    ['entityName' => 'customer'],
                ],
            ],
        ], $context);
    }

    public function uninstall(Context $context): void
    {
        $existing = $this->findExistingId($context);
        if ($existing === null) {
            return;
        }

        $this->customFieldSetRepository->delete([['id' => $existing]], $context);
    }

    private function findExistingId(Context $context): ?string
    {
        $criteria = (new Criteria())->addFilter(new EqualsFilter('name', self::SET_NAME));
        $result = $this->customFieldSetRepository->searchIds($criteria, $context);
        $first = $result->firstId();

        return is_string($first) ? $first : null;
    }
}
