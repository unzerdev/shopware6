<?php

declare(strict_types=1);

namespace HeidelPayment\Services;

use heidelpayPHP\Resources\Payment;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Framework\Context;

interface TransactionStateHandlerInterface
{
    public function transformTransactionState(
        OrderTransactionEntity $transaction,
        Payment $payment,
        Context $context
    ): void;
}
