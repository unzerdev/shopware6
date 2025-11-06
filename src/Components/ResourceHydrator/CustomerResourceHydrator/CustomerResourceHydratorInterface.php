<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\ResourceHydrator\CustomerResourceHydrator;

use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderAddress\OrderAddressEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderCustomer\OrderCustomerEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Framework\Context;
use UnzerSDK\Resources\Customer as UnzerCustomer;

interface CustomerResourceHydratorInterface
{
    public function getShopCustomerId(CustomerEntity $customer, ?OrderAddressEntity $orderBillingAddress = null): string;

    public function hydrateObject(string $paymentMethodId, OrderCustomerEntity|CustomerEntity $customer, Context $context, ?OrderTransactionEntity $orderTransaction = null): UnzerCustomer;

    public function hydrateExistingCustomer(UnzerCustomer $unzerCustomer, OrderCustomerEntity|CustomerEntity $customer, Context $context, ?OrderTransactionEntity $orderTransaction = null): UnzerCustomer;
}
