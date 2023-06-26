<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\PaymentHandler\Traits;

use RuntimeException;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenContainerEvent;
use UnzerPayment6\Components\Struct\TransferInformation\TransferInformation;
use UnzerPayment6\Installer\CustomFieldInstaller;
use UnzerSDK\Resources\TransactionTypes\Authorization;
use UnzerSDK\Resources\TransactionTypes\Charge;

/**
 * @property EntityRepository $transactionRepository
 */
trait HasTransferInfoTrait
{
    private function saveTransferInfo(OrderTransactionEntity $orderTransactionEntity, Context $context): EntityWrittenContainerEvent
    {
        if (!isset($this->transactionRepository)) {
            throw new RuntimeException('TransactionRepository can not be null');
        }

        if (!isset($this->payment)) {
            throw new RuntimeException('Payment can not be null');
        }

        /** @var null|Charge $charge */
        $charge = $this->payment->getChargeByIndex(0);

        if (!isset($charge)) {
            throw new RuntimeException('Payment has not been charged');
        }

        return $this->transactionRepository->upsert([
            [
                'id'           => $orderTransactionEntity->getId(),
                'customFields' => array_merge(
                    $orderTransactionEntity->getCustomFields() ?? [],
                    [
                        CustomFieldInstaller::UNZER_PAYMENT_TRANSFER_INFO => new TransferInformation($charge),
                    ]
                ),
            ],
        ], $context);
    }

    private function saveTransferInfoFromAuthorize(OrderTransactionEntity $orderTransactionEntity, Context $context): EntityWrittenContainerEvent
    {
        if (!isset($this->transactionRepository)) {
            throw new RuntimeException('TransactionRepository can not be null');
        }

        if (!isset($this->payment)) {
            throw new RuntimeException('Payment can not be null');
        }

        /** @var null|Authorization $authorization */
        $authorization = $this->payment->getAuthorization();

        if (!isset($authorization)) {
            throw new RuntimeException('Payment has not been authorized');
        }

        return $this->transactionRepository->upsert([
            [
                'id'           => $orderTransactionEntity->getId(),
                'customFields' => array_merge(
                    $orderTransactionEntity->getCustomFields() ?? [],
                    [
                        CustomFieldInstaller::UNZER_PAYMENT_TRANSFER_INFO => new TransferInformation($authorization),
                    ]
                ),
            ],
        ], $context);
    }
}
