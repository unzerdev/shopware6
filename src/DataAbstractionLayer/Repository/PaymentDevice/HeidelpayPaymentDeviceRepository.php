<?php

declare(strict_types=1);

namespace HeidelPayment\DataAbstractionLayer\Repository\PaymentDevice;

use HeidelPayment\DataAbstractionLayer\Entity\PaymentDevice\HeidelpayPaymentDeviceEntity;
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

    /**
     * {@inheritdoc}
     */
    public function getCollectionByCustomerId(string $customerId, Context $context): EntitySearchResult
    {
        $criteria = new Criteria();
        $criteria->addFilter(
            new EqualsFilter('heidelpay_payment_device.customerId', $customerId)
        );

        return $this->entityRepository->search($criteria, $context);
    }

    /**
     * {@inheritdoc}
     */
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

    /**
     * {@inheritdoc}
     */
    public function remove(string $id, Context $context): EntityWrittenContainerEvent
    {
        return $this->entityRepository->delete([
            ['id' => $id],
        ], $context);
    }

    /**
     * {@inheritdoc}
     */
    public function exists(string $typeId, Context $context): bool
    {
        $criteria = new Criteria();
        $criteria->addFilter(
            new EqualsFilter('typeId', $typeId)
        );

        return $this->entityRepository->search($criteria, $context)->getTotal() > 0;
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $id, Context $context): ?HeidelpayPaymentDeviceEntity
    {
        $criteria = new Criteria();
        $criteria->addFilter(
            new EqualsFilter('id', $id)
        );

        $result = $this->entityRepository->search($criteria, $context);

        return $result->getTotal() !== 0 ? $result->getEntities()->first() : null;
    }
}
