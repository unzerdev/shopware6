<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\ResourceHydrator\PaymentResourceHydrator;

use heidelpayPHP\Resources\Payment;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;

interface PaymentResourceHydratorInterface
{
    /**
     * All amounts are provided as int instead of boolean due to the serializer formatting
     *
     * @see https://bugs.php.net/bug.php?id=74221
     */
    public function hydrateArray(Payment $payment, ?OrderTransactionEntity $orderTransaction): array;
}
