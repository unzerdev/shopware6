<?php

declare(strict_types=1);

namespace UnzerPayment6\DataAbstractionLayer\Repository\PaymentDevice;

use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenContainerEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Uuid\Uuid;
use UnzerPayment6\DataAbstractionLayer\Entity\PaymentDevice\UnzerPaymentDeviceEntity;

readonly class UnzerPaymentDeviceRepository implements UnzerPaymentDeviceRepositoryInterface
{
    public const DEFAULT_ADDRESS_HASH = 'no_hash';

    public function __construct(
        private EntityRepository $entityRepository
    ) {
    }

    public function getCollectionByCustomer(CustomerEntity $customer, Context $context, ?string $deviceType = null): EntitySearchResult
    {
        $criteria = new Criteria();
        $criteria->addFilter(
            new EqualsFilter('customerId', $customer->getId())
        );

        if ($deviceType) {
            $criteria->addFilter(new EqualsFilter('deviceType', $deviceType));
        }

        return $this->entityRepository->search($criteria, $context);
    }

    public function create(
        CustomerEntity $customer,
        string $deviceType,
        string $typeId,
        array $data,
        Context $context
    ): EntityWrittenContainerEvent {
        $createData = [
            'id' => Uuid::randomHex(),
            'deviceType' => $deviceType,
            'typeId' => $typeId,
            'data' => $data,
            'customerId' => $customer->getId(),
            'addressHash' => self::DEFAULT_ADDRESS_HASH,
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

    public function read(string $id, Context $context): ?UnzerPaymentDeviceEntity
    {
        $criteria = new Criteria([$id]);

        $result = $this->entityRepository->search($criteria, $context);

        return $result->getTotal() !== 0 ? $result->getEntities()->first() : null;
    }

    public function getByPaymentTypeId(string $paymentTypeId, Context $context): ?UnzerPaymentDeviceEntity
    {
        $criteria = new Criteria();
        $criteria->addFilter(
            new EqualsFilter('typeId', $paymentTypeId)
        );

        $result = $this->entityRepository->search($criteria, $context);

        return $result->getTotal() !== 0 ? $result->getEntities()->first() : null;
    }
}
