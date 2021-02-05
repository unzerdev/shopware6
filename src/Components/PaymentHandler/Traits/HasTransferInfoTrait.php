<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\PaymentHandler\Traits;

use heidelpayPHP\Resources\TransactionTypes\Charge;
use RuntimeException;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenContainerEvent;
use UnzerPayment6\Components\Struct\TransferInformation\TransferInformation;
use UnzerPayment6\DataAbstractionLayer\Repository\TransferInfo\UnzerPaymentTransferInfoRepositoryInterface;

trait HasTransferInfoTrait
{
    /** @var UnzerPaymentTransferInfoRepositoryInterface */
    protected $transferInfoRepository;

    private function saveTransferInfo(string $transactionId, ?string $transactionVersionId, Context $context): EntityWrittenContainerEvent
    {
        if (!isset($this->transferInfoRepository)) {
            throw new RuntimeException('TransferInfoRepository can not be null');
        }

        if (!isset($this->payment)) {
            throw new RuntimeException('Payment can not be null');
        }

        /** @var null|Charge $charge */
        $charge = $this->payment->getChargeByIndex(0);

        if (!isset($charge)) {
            throw new RuntimeException('Payment has not been charged');
        }

        $transferInfo = (new TransferInformation($transactionId, $transactionVersionId))->fromCharge($charge);

        return $this->transferInfoRepository->create($transferInfo, $context);
    }
}
