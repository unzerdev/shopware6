<?php

namespace HeidelPayment\Services;

use heidelpayPHP\Resources\Payment;
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\Framework\Context;

interface TransactionStateHandlerInterface
{
    public function transformTransactionState(Context $context, AsyncPaymentTransactionStruct $transaction, Payment $payment);
}
