<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\ResourceHydrator\PaymentResourceHydrator;

use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use UnzerSDK\Resources\Payment;

interface PaymentResourceHydratorInterface
{
    public function hydrateArray(Payment $resource, ?OrderTransactionEntity $orderTransaction): array;
}
