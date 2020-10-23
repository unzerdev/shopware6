<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\TransactionStateHandler;

use heidelpayPHP\Resources\Payment;
use Shopware\Core\Framework\Context;

interface TransactionStateHandlerInterface
{
    /**
     * Determines transition by payment and executes the transition if valid
     */
    public function transformTransactionState(
        string $transactionId,
        Payment $payment,
        Context $context
    ): void;
}
