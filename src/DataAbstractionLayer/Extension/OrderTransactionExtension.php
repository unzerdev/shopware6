<?php

declare(strict_types=1);

namespace HeidelPayment6\DataAbstractionLayer\Extension;

use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityExtensionInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Runtime;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ObjectField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

class OrderTransactionExtension implements EntityExtensionInterface
{
    /**
     * {@inheritdoc}
     */
    public function extendFields(FieldCollection $collection): void
    {
        $collection->add(
            (new ObjectField('transfer_info', 'transferInfo'))->addFlags(new Runtime())
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getDefinitionClass(): string
    {
        return OrderTransactionDefinition::class;
    }
}
