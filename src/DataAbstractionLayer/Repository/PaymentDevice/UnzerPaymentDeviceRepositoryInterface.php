<?php

declare(strict_types=1);

namespace UnzerPayment6\DataAbstractionLayer\Repository\PaymentDevice;

use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenContainerEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use UnzerPayment6\DataAbstractionLayer\Entity\PaymentDevice\UnzerPaymentDeviceEntity;

interface UnzerPaymentDeviceRepositoryInterface
{
    public function getCollectionByCustomer(CustomerEntity $customer, Context $context, string $deviceType = null): EntitySearchResult;

    public function create(CustomerEntity $customer, string $deviceType, string $typeId, array $data, Context $context): EntityWrittenContainerEvent;

    public function remove(string $id, Context $context): EntityWrittenContainerEvent;

    public function exists(string $typeId, Context $context): bool;

    public function read(string $id, Context $context): ?UnzerPaymentDeviceEntity;

    public function getByPaymentTypeId(string $paymentTypeId, Context $context): ?UnzerPaymentDeviceEntity;
}
