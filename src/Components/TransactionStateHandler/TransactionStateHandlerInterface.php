<?php

declare(strict_types=1);

namespace HeidelPayment6\Components\TransactionStateHandler;

use heidelpayPHP\Resources\Payment;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Framework\Context;

interface TransactionStateHandlerInterface
{
    /**
     * Determines transition by payment and executes the transition if valid
     */
    public function transformTransactionState(
        OrderTransactionEntity $transaction,
        Payment $payment,
        Context $context
    ): void;
}
