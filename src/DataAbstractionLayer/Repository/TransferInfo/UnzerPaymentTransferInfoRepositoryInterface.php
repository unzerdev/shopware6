<?php

declare(strict_types=1);

namespace UnzerPayment6\DataAbstractionLayer\Repository\TransferInfo;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenContainerEvent;
use UnzerPayment6\Components\Struct\TransferInformation\TransferInformation;
use UnzerPayment6\DataAbstractionLayer\Entity\TransferInfo\UnzerPaymentTransferInfoEntity;

interface UnzerPaymentTransferInfoRepositoryInterface
{
    public function create(string $transactionId, TransferInformation $data, Context $context): EntityWrittenContainerEvent;

    public function remove(string $id, Context $context): EntityWrittenContainerEvent;

    public function exists(string $transactionId, Context $context): bool;

    public function read(string $transactionId, Context $context): ?UnzerPaymentTransferInfoEntity;
}
