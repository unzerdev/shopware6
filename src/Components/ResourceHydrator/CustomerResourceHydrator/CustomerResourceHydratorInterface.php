<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\ResourceHydrator\CustomerResourceHydrator;

use heidelpayPHP\Resources\AbstractHeidelpayResource;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

interface CustomerResourceHydratorInterface
{
    public function hydrateObject(string $paymentMethodId, SalesChannelContext $channelContext): AbstractHeidelpayResource;

    public function hydrateExistingCustomer(AbstractHeidelpayResource $unzerCustomer, SalesChannelContext $salesChannelContext): AbstractHeidelpayResource;
}
