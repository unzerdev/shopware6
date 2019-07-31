<?php

declare(strict_types=1);

namespace HeidelPayment\Components\ResourceHydrator;

use heidelpayPHP\Resources\AbstractHeidelpayResource;
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

interface ResourceHydratorInterface
{
    public function hydrateObject(SalesChannelContext $channelContext, ?AsyncPaymentTransactionStruct $transaction = null): AbstractHeidelpayResource;
}
