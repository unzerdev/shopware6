<?php

namespace HeidelPayment\Services\Heidelpay\Hydrator;

use heidelpayPHP\Resources\AbstractHeidelpayResource;
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

interface HeidelpayHydratorInterface
{
    public function hydrateObject(SalesChannelContext $channelContext, ?AsyncPaymentTransactionStruct $transaction = null): AbstractHeidelpayResource;
}
