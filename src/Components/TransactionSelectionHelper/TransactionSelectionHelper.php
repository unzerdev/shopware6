<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\TransactionSelectionHelper;

use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStates;
use Shopware\Core\Checkout\Order\OrderEntity;
use UnzerPayment6\Installer\PaymentInstaller;

class TransactionSelectionHelper implements TransactionSelectionHelperInterface
{
    public function getBestUnzerTransaction(OrderEntity $orderEntity): ?OrderTransactionEntity
    {
        $transactions = $orderEntity->getTransactions();

        if ($transactions === null) {
            return null;
        }

        $transactions = $this->filterByPaymentMethod($transactions);

        if ($transactions->count() <= 1) {
            return $transactions->first();
        }

        $transactions = $this->filterByState($transactions);

        return $this->getLatestTransaction($transactions);
    }

    public function getLatestTransaction(OrderTransactionCollection $transactions): ?OrderTransactionEntity
    {
        if ($transactions->count() > 1) {
            $latest = [];

            foreach ($transactions as $transaction) {
                if ($transaction->getCreatedAt() === null) {
                    continue;
                }

                if (empty($latest) || (array_key_exists('timestamp', $latest) && $latest['timestamp'] < $transaction->getCreatedAt()->getTimestamp())) {
                    $latest = [
                        'timestamp' => $transaction->getCreatedAt()->getTimestamp(),
                        'id'        => $transaction->getId(),
                    ];
                }
            }

            if (!empty($latest) && array_key_exists('id', $latest)) {
                $latestTransaction = $transactions->get($latest['id']);

                if ($latestTransaction !== null) {
                    return $latestTransaction;
                }
            }
        }

        return $transactions->first();
    }

    protected function filterByPaymentMethod(OrderTransactionCollection $transactions): OrderTransactionCollection
    {
        return $transactions->filter(static function (OrderTransactionEntity $transaction) {
            return in_array($transaction->getPaymentMethodId(), PaymentInstaller::PAYMENT_METHOD_IDS);
        });
    }

    protected function filterByState(OrderTransactionCollection $transactions): OrderTransactionCollection
    {
        return $transactions->filter(static function (OrderTransactionEntity $transaction) {
            if (null === $transaction->getStateMachineState() || null === $transaction->getPaymentMethod()) {
                return false;
            }

            $technicalName = $transaction->getStateMachineState()->getTechnicalName();

            if ($technicalName === OrderTransactionStates::STATE_CANCELLED || $technicalName === OrderTransactionStates::STATE_FAILED) {
                return false;
            }

            return true;
        });
    }
}
