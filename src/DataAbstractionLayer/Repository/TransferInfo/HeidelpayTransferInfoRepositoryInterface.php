<?php

declare(strict_types=1);

namespace HeidelPayment6\DataAbstractionLayer\Repository\TransferInfo;

use HeidelPayment6\Components\Struct\TransferInformation\TransferInformation;
use HeidelPayment6\DataAbstractionLayer\Entity\TransferInfo\HeidelpayTransferInfoEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenContainerEvent;

interface HeidelpayTransferInfoRepositoryInterface
{
    public function create(string $transactionId, TransferInformation $data, Context $context): EntityWrittenContainerEvent;

    public function remove(string $id, Context $context): EntityWrittenContainerEvent;

    public function exists(string $transactionId, Context $context): bool;

    public function read(string $transactionId, Context $context): ?HeidelpayTransferInfoEntity;
}
