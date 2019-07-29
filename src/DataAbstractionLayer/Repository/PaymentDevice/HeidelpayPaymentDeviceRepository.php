<?php

namespace HeidelPayment\DataAbstractionLayer\Repository\PaymentDevice;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenContainerEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Uuid\Uuid;

class HeidelpayPaymentDeviceRepository implements HeidelpayPaymentDeviceRepositoryInterface
{
    /** @var EntityRepositoryInterface */
    private $entityRepository;

    public function __construct(EntityRepositoryInterface $entityRepository)
    {
        $this->entityRepository = $entityRepository;
    }

    public function getCollectionByCustomerId(string $customerId, Context $context): EntitySearchResult
    {
        $criteria = new Criteria();
        $criteria->addFilter(
            new EqualsFilter('heidelpay_payment_device.customerId', $customerId)
        );

        return $this->entityRepository->search($criteria, $context);
    }

    public function create(
        string $customerId,
        string $deviceType,
        string $typeId,
        array $data,
        Context $context
    ): EntityWrittenContainerEvent {
        $createData = [
            'id'         => Uuid::randomHex(),
            'deviceType' => $deviceType,
            'typeId'     => $typeId,
            'data'       => $data,
            'customerId' => $customerId,
        ];

        return $this->entityRepository->create([
            $createData,
        ], $context);
    }

    public function remove(string $id, Context $context): EntityWrittenContainerEvent
    {
        return $this->entityRepository->delete([
            ['id' => $id],
        ], $context);
    }

    public function exists(string $typeId, Context $context): bool
    {
        $criteria = new Criteria();
        $criteria->addFilter(
            new EqualsFilter('typeId', $typeId)
        );

        return $this->entityRepository->search($criteria, $context)->getTotal() > 0;
    }
}
