<?php

declare(strict_types=1);

namespace UnzerPayment6\DataAbstractionLayer\Repository\TransferInfo;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenContainerEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use UnzerPayment6\Components\Struct\TransferInformation\TransferInformation;
use UnzerPayment6\DataAbstractionLayer\Entity\TransferInfo\UnzerPaymentTransferInfoEntity;

class UnzerPaymentTransferInfoRepository implements UnzerPaymentTransferInfoRepositoryInterface
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
    public function create(
        TransferInformation $transferInformation,
        Context $context
    ): EntityWrittenContainerEvent {
        return $this->entityRepository->create([$transferInformation->getEntityData()], $context);
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
    public function exists(string $transactionId, Context $context): bool
    {
        $criteria = new Criteria();
        $criteria->addFilter(
            new EqualsFilter('transactionId', $transactionId)
        );

        return $this->entityRepository->search($criteria, $context)->getTotal() > 0;
    }

    /**
     * {@inheritdoc}
     */
    public function read(string $transactionId, Context $context): ?UnzerPaymentTransferInfoEntity
    {
        $criteria = new Criteria();
        $criteria->addFilter(
            new EqualsFilter('transactionId', $transactionId)
        );

        $result = $this->entityRepository->search($criteria, $context);

        return $result->getTotal() !== 0 ? $result->getEntities()->first() : null;
    }
}
