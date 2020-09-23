<?php

declare(strict_types=1);

namespace UnzerPayment6\DataAbstractionLayer\Entity\TransferInfo;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void add(UnzerPaymentTransferInfoEntity $entity)
 * @method void set(string $key, UnzerPaymentTransferInfoEntity $entity)
 * @method UnzerPaymentTransferInfoEntity[]    getIterator()
 * @method UnzerPaymentTransferInfoEntity[]    getElements()
 * @method null|UnzerPaymentTransferInfoEntity get(string $key)
 * @method null|UnzerPaymentTransferInfoEntity first()
 * @method null|UnzerPaymentTransferInfoEntity last()
 */
class UnzerPaymentTransferInfoEntityCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return HeidelpayTransferInfoEntity::class;
    }
}
