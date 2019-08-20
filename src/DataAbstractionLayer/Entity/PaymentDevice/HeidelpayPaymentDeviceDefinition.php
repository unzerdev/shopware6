<?php

declare(strict_types=1);

namespace HeidelPayment\DataAbstractionLayer\Entity\PaymentDevice;

use Shopware\Core\Checkout\Customer\CustomerDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\CreatedAtField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\JsonField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\UpdatedAtField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

class HeidelpayPaymentDeviceDefinition extends EntityDefinition
{
    public function getEntityName(): string
    {
        return 'heidelpay_payment_device';
    }

    public function getCollectionClass(): string
    {
        return HeidelpayPaymentDeviceCollection::class;
    }

    public function getEntityClass(): string
    {
        return HeidelpayPaymentDeviceEntity::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->setFlags(new PrimaryKey(), new Required()),

            (new FkField('customer_id', 'customerId', CustomerDefinition::class))->addFlags(new Required()),

            (new StringField('device_type', 'deviceType'))->setFlags(new Required()),
            (new StringField('type_id', 'typeId'))->setFlags(new Required()),
            (new JsonField('data', 'data'))->setFlags(new Required()),
            (new StringField('address_hash', 'addressHash'))->setFlags(new Required()),

            new ManyToOneAssociationField('customer', 'customer_id', CustomerDefinition::class, 'id', false),

            new CreatedAtField(),
            new UpdatedAtField(),
        ]);
    }
}
