<?php

namespace HeidelPayment\DataAbstractionLayer\Repository\PaymentDevice;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenContainerEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;

interface HeidelpayPaymentDeviceRepositoryInterface
{
    public function __construct(EntityRepositoryInterface $entityRepository);

    public function getCollectionByCustomerId(string $customerId, Context $context): EntitySearchResult;

    public function create(string $customerId, string $deviceType, string $typeId, array $data, Context $context): EntityWrittenContainerEvent;

    public function remove(string $id, Context $context): EntityWrittenContainerEvent;

    public function exists(string $typeId, Context $context): bool;
}
