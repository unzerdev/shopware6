<?php

declare(strict_types=1);

namespace HeidelPayment6\DataAbstractionLayer\Repository\PaymentDevice;

use HeidelPayment6\Components\AddressHashGenerator\AddressHashGeneratorInterface;
use HeidelPayment6\DataAbstractionLayer\Entity\PaymentDevice\HeidelpayPaymentDeviceEntity;
use Shopware\Core\Checkout\Customer\CustomerEntity;
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

    /** @var AddressHashGeneratorInterface */
    private $addressHashService;

    public function __construct(EntityRepositoryInterface $entityRepository, AddressHashGeneratorInterface $addressHashGenerator)
    {
        $this->entityRepository   = $entityRepository;
        $this->addressHashService = $addressHashGenerator;
    }

    /**
     * {@inheritdoc}
     */
    public function getCollectionByCustomer(CustomerEntity $customer, Context $context): EntitySearchResult
    {
        $addressHash = $this->addressHashService->generateHash($customer->getActiveBillingAddress(), $customer->getActiveShippingAddress());

        $criteria = new Criteria();
        $criteria->addFilter(
            new EqualsFilter('heidelpay_payment_device.customerId', $customer->getId()),
            new EqualsFilter('heidelpay_payment_device.addressHash', $addressHash)
        );

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
