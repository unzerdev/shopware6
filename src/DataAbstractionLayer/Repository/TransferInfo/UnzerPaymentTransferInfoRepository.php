<?php

declare(strict_types=1);

namespace UnzerPayment6\DataAbstractionLayer\Repository\TransferInfo;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenContainerEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Uuid\Uuid;
use UnzerPayment6\Components\Struct\TransferInformation\TransferInformation;
use UnzerPayment6\DataAbstractionLayer\Entity\TransferInfo\HeidelpayTransferInfoEntity;

class HeidelpayTransferInfoRepository implements HeidelpayTransferInfoRepositoryInterface
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
        string $transactionId,
        TransferInformation $data,
        Context $context
    ): EntityWrittenContainerEvent {
        $transferInfoData = [
            'transactionId' => $transactionId,
            'descriptor'    => $data->getDescriptor(),
            'holder'        => $data->getHolder(),
            'amount'        => $data->getAmount(),
            'iban'          => $data->getIban(),
            'bic'           => $data->getBic(),
            'id'            => Uuid::randomHex(),
        ];

        return $this->entityRepository->create([
            $transferInfoData,
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
    public function read(string $transactionId, Context $context): ?HeidelpayTransferInfoEntity
    {
        $criteria = new Criteria();
        $criteria->addFilter(
            new EqualsFilter('transactionId', $transactionId)
        );

        $result = $this->entityRepository->search($criteria, $context);

        return $result->getTotal() !== 0 ? $result->getEntities()->first() : null;
    }
}
