<?php

declare(strict_types=1);

namespace HeidelPayment6\DataAbstractionLayer\Entity\PaymentDevice;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void                              add(HeidelpayPaymentDeviceEntity $entity)
 * @method void                              set(string $key, HeidelpayPaymentDeviceEntity $entity)
 * @method HeidelpayPaymentDeviceEntity[]    getIterator()
 * @method HeidelpayPaymentDeviceEntity[]    getElements()
 * @method null|HeidelpayPaymentDeviceEntity get(string $key)
 * @method null|HeidelpayPaymentDeviceEntity first()
 * @method null|HeidelpayPaymentDeviceEntity last()
 */
class HeidelpayPaymentDeviceCollection extends EntityCollection
{
    public function filterByDeviceType(string $deviceType): self
    {
        return $this->filter(function (HeidelpayPaymentDeviceEntity $deviceEntity) use ($deviceType) {
            return $deviceEntity->getDeviceType() === $deviceType;
        });
    }

    protected function getExpectedClass(): string
    {
        return HeidelpayPaymentDeviceEntity::class;
    }
}
