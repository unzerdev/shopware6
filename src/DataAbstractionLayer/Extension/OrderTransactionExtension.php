<?php

declare(strict_types=1);

namespace UnzerPayment6\DataAbstractionLayer\Extension;

use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityExtension;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Runtime;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ObjectField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Shopware\Core\Framework\Log\Package;

#[Package('checkout')]
class OrderTransactionExtension extends EntityExtension
{
    public const TRANSFER_INFO_EXTENSION = 'transferInfo';

    public function extendFields(FieldCollection $collection): void
    {
        $collection->add(
            (new ObjectField('transfer_info', self::TRANSFER_INFO_EXTENSION))->addFlags(new Runtime())
        );
    }

    public function getEntityName(): string
    {
        return OrderTransactionDefinition::ENTITY_NAME;
    }
}
