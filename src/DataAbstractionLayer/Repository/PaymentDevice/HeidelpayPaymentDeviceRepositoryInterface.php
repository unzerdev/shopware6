<?php

declare(strict_types=1);

namespace HeidelPayment6\DataAbstractionLayer\Repository\PaymentDevice;

use HeidelPayment6\DataAbstractionLayer\Entity\PaymentDevice\HeidelpayPaymentDeviceEntity;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenContainerEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;

interface HeidelpayPaymentDeviceRepositoryInterface
{
    public function getCollectionByCustomer(CustomerEntity $customer, string $deviceType, Context $context): EntitySearchResult;

    public function create(CustomerEntity $customer, string $deviceType, string $typeId, array $data, Context $context): EntityWrittenContainerEvent;

    public function remove(string $id, Context $context): EntityWrittenContainerEvent;

    public function exists(string $typeId, Context $context): bool;

    public function read(string $id, Context $context): ?HeidelpayPaymentDeviceEntity;
}
