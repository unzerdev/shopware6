<?php

declare(strict_types=1);

namespace HeidelPayment6\DataAbstractionLayer\Entity\TransferInfo;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void                             add(HeidelpayTransferInfoEntity $entity)
 * @method void                             set(string $key, HeidelpayTransferInfoEntity $entity)
 * @method HeidelpayTransferInfoEntity[]    getIterator()
 * @method HeidelpayTransferInfoEntity[]    getElements()
 * @method null|HeidelpayTransferInfoEntity get(string $key)
 * @method null|HeidelpayTransferInfoEntity first()
 * @method null|HeidelpayTransferInfoEntity last()
 */
class HeidelpayTransferInfoEntityCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return HeidelpayTransferInfoEntity::class;
    }
}
