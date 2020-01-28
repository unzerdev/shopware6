<?php

declare(strict_types=1);

namespace HeidelPayment6\Components\PaymentHandler\Traits;

use HeidelPayment6\Components\Struct\TransferInformation\TransferInformation;
use HeidelPayment6\DataAbstractionLayer\Repository\TransferInfo\HeidelpayTransferInfoRepositoryInterface;
use heidelpayPHP\Resources\TransactionTypes\Charge;
use RuntimeException;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenContainerEvent;

trait HasTransferInfoTrait
{
    /** @var HeidelpayTransferInfoRepositoryInterface */
    protected $transferInfoRepository;

    private function saveTransferInfo(string $transactionId, Charge $charge, Context $context): EntityWrittenContainerEvent
    {
        if ($this->transferInfoRepository === null) {
            throw new RuntimeException('TransferInfoRepository can not be null');
        }

        $transferInfo = (new TransferInformation())->fromCharge($charge);

        return $this->transferInfoRepository->create($transactionId, $transferInfo, $context);
    }
}
