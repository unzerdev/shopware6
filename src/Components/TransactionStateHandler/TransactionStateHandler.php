<?php

declare(strict_types=1);

namespace HeidelPayment\Components\TransactionStateHandler;

use heidelpayPHP\Resources\Payment;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler;
use Shopware\Core\Framework\Context;

class TransactionStateHandler implements TransactionStateHandlerInterface
{
    public const STATE_OPEN = 'open';

    /** @var OrderTransactionStateHandler */
    private $orderTransactionStateHandler;

    public function __construct(OrderTransactionStateHandler $orderTransactionStateHandler)
    {
        $this->orderTransactionStateHandler = $orderTransactionStateHandler;
    }

    /**
     * {@inheritdoc}
     */
    public function transformTransactionState(
        OrderTransactionEntity $transaction,
        Payment $payment,
        Context $context
    ): void {
        $transactionId = $transaction->getId();

        if ($payment->isPartlyPaid()) {
            $this->orderTransactionStateHandler->payPartially($transactionId, $context);
        } elseif ($payment->isCompleted()) {
            $this->orderTransactionStateHandler->pay($transactionId, $context);
        } elseif ($payment->isCanceled()) {
            $this->orderTransactionStateHandler->cancel($transactionId, $context);
        } elseif ($payment->isChargeBack()) {
            $this->orderTransactionStateHandler->payPartially($transactionId, $context);
        } elseif ($transaction->getStateMachineState()->getTechnicalName() !== self::STATE_OPEN) {
            $this->orderTransactionStateHandler->reopen($transactionId, $context);
        }
    }
}
