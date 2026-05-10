<?php

declare(strict_types=1);

namespace Vineethkrishnan\XrechnungKitShopware\Core\Content\XrechnungInvoice;

use Shopware\Core\Checkout\Order\OrderDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\DateTimeField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\JsonField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ReferenceVersionField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

class XrechnungInvoiceDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'xrechnung_kit_invoice';

    public const STATUS_PENDING = 'pending';
    public const STATUS_GENERATED = 'generated';
    public const STATUS_INVALID = 'invalid';
    public const STATUS_FAILED = 'failed';

    public const KOSIT_PASS = 'pass';
    public const KOSIT_FAIL = 'fail';
    public const KOSIT_SKIPPED = 'skipped';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getEntityClass(): string
    {
        return XrechnungInvoiceEntity::class;
    }

    public function getCollectionClass(): string
    {
        return XrechnungInvoiceCollection::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new PrimaryKey(), new Required()),

            (new FkField('order_id', 'orderId', OrderDefinition::class))->addFlags(new Required()),
            (new ReferenceVersionField(OrderDefinition::class))->addFlags(new Required()),

            (new StringField('status', 'status'))->addFlags(new Required()),
            new StringField('generated_path', 'generatedPath', 2048),
            new JsonField('errors', 'errors'),
            new DateTimeField('generated_at', 'generatedAt'),
            new JsonField('mapping_snapshot', 'mappingSnapshot'),
            new StringField('validator_version', 'validatorVersion', 64),
            new StringField('kosit_result', 'kositResult', 32),

            new ManyToOneAssociationField('order', 'order_id', OrderDefinition::class, 'id'),
        ]);
    }
}
