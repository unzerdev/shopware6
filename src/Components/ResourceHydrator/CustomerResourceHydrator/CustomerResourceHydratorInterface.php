<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\ResourceHydrator\CustomerResourceHydrator;

use Shopware\Core\Checkout\Order\Aggregate\OrderCustomer\OrderCustomerEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Framework\Context;
use UnzerSDK\Resources\Customer as UnzerCustomer;

interface CustomerResourceHydratorInterface
{
    public function hydrateObject(string $paymentMethodId, OrderCustomerEntity $orderCustomer, OrderTransactionEntity $orderTransaction, Context $context): UnzerCustomer;

    public function hydrateExistingCustomer(UnzerCustomer $unzerCustomer, OrderCustomerEntity $orderCustomer, OrderTransactionEntity $orderTransaction, Context $context): UnzerCustomer;
}
