<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\ResourceHydrator\PaymentResourceHydrator;

use heidelpayPHP\Resources\Payment;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;

interface PaymentResourceHydratorInterface
{
    public function hydrateArray(Payment $payment, ?OrderTransactionEntity $orderTransaction): array;
}
