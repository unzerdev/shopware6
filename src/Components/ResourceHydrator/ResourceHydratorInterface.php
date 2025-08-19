<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\ResourceHydrator;

use Shopware\Core\System\SalesChannel\SalesChannelContext;
use UnzerSDK\Resources\AbstractUnzerResource;

interface ResourceHydratorInterface
{
    public function hydrateObject(SalesChannelContext $channelContext, $transaction = null): AbstractUnzerResource;
}
