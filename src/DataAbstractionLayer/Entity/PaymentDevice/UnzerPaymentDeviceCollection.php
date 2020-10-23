<?php

declare(strict_types=1);

namespace UnzerPayment6\DataAbstractionLayer\Entity\PaymentDevice;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void                          add(UnzerPaymentDeviceEntity $entity)
 * @method void                          set(string $key, UnzerPaymentDeviceEntity $entity)
 * @method UnzerPaymentDeviceEntity[]    getIterator()
 * @method UnzerPaymentDeviceEntity[]    getElements()
 * @method null|UnzerPaymentDeviceEntity get(string $key)
 * @method null|UnzerPaymentDeviceEntity first()
 * @method null|UnzerPaymentDeviceEntity last()
 */
class UnzerPaymentDeviceCollection extends EntityCollection
{
    public function filterByDeviceType(string $deviceType): self
    {
        return $this->filter(function (UnzerPaymentDeviceEntity $deviceEntity) use ($deviceType) {
            return $deviceEntity->getDeviceType() === $deviceType;
        });
    }

    protected function getExpectedClass(): string
    {
        return UnzerPaymentDeviceEntity::class;
    }
}
