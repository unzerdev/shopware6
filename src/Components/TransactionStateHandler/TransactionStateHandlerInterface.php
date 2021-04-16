<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\TransactionStateHandler;

use RuntimeException;
use Shopware\Core\Framework\Context;
use UnzerSDK\Resources\Payment;

interface TransactionStateHandlerInterface
{
    /**
     * Determines transition by payment and executes the transition if valid
     *
     * @throws RuntimeException
     */
    public function transformTransactionState(
        string $transactionId,
        Payment $payment,
        Context $context
    ): void;

    public function fail(string $transactionId, Context $context): void;

    public function pay(string $transactionId, Context $context): void;
}
