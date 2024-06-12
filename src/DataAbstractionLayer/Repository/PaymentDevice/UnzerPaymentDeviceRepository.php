<?php

declare(strict_types=1);

namespace UnzerPayment6\DataAbstractionLayer\Repository\PaymentDevice;

use RuntimeException;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenContainerEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Uuid\Uuid;
use UnzerPayment6\Components\AddressHashGenerator\AddressHashGeneratorInterface;
use UnzerPayment6\DataAbstractionLayer\Entity\PaymentDevice\UnzerPaymentDeviceEntity;

class UnzerPaymentDeviceRepository implements UnzerPaymentDeviceRepositoryInterface
{

    public function __construct(
        private readonly EntityRepository $entityRepository,
        private readonly AddressHashGeneratorInterface $addressHashService
    )
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getCollectionByCustomer(CustomerEntity $customer, Context $context, string $deviceType = null): EntitySearchResult
    {
        if ($customer->getActiveBillingAddress() === null || $customer->getActiveShippingAddress() === null) {
            throw new RuntimeException('Customer has no active billing or shipping address');
        }

        $addressHash = $this->addressHashService->generateHash($customer->getActiveBillingAddress(), $customer->getActiveShippingAddress());

        $criteria = new Criteria();
        $criteria->addFilter(
            new EqualsFilter('customerId', $customer->getId()),
            new EqualsFilter('addressHash', $addressHash)
        );

        if ($deviceType) {
            $criteria->addFilter(new EqualsFilter('deviceType', $deviceType));
        }

        return $this->entityRepository->search($criteria, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function create(
        CustomerEntity $customer,
        string $deviceType,
        string $typeId,
        array $data,
        Context $context
    ): EntityWrittenContainerEvent {
        if ($customer->getActiveBillingAddress() === null || $customer->getActiveShippingAddress() === null) {
            throw new RuntimeException('Customer has no active billing or shipping address');
        }

        $addressHash = $this->addressHashService->generateHash($customer->getActiveBillingAddress(), $customer->getActiveShippingAddress());

        $createData = [
            'id'          => Uuid::randomHex(),
            'deviceType'  => $deviceType,
            'typeId'      => $typeId,
            'data'        => $data,
            'customerId'  => $customer->getId(),
            'addressHash' => $addressHash,
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
