<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\ResourceHydrator\CustomerResourceHydrator;

use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderAddress\OrderAddressEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use UnzerSDK\Resources\AbstractUnzerResource;

interface CustomerResourceHydratorInterface
{
    public function getShopCustomerId(CustomerEntity $customer, ?OrderAddressEntity $orderBillingAddress = null): string;

    public function hydrateObject(string $paymentMethodId, SalesChannelContext $channelContext, ?OrderTransactionEntity $orderTransaction = null): AbstractUnzerResource;

    public function hydrateExistingCustomer(AbstractUnzerResource $unzerCustomer, SalesChannelContext $salesChannelContext, ?OrderTransactionEntity $orderTransaction = null): AbstractUnzerResource;
}
