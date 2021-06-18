<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\ResourceHydrator\CustomerResourceHydrator;

use Shopware\Core\System\SalesChannel\SalesChannelContext;
use UnzerSDK\Resources\AbstractUnzerResource;

interface CustomerResourceHydratorInterface
{
    public function hydrateObject(string $paymentMethodId, SalesChannelContext $channelContext): AbstractUnzerResource;

    public function hydrateExistingCustomer(AbstractUnzerResource $unzerCustomer, SalesChannelContext $salesChannelContext): AbstractUnzerResource;
}
