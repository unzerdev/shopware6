<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\ResourceHydrator;

use heidelpayPHP\Resources\AbstractHeidelpayResource;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

interface ResourceHydratorInterface
{
    /**
     * @param null|AsyncPaymentTransactionStruct|OrderTransactionEntity $transaction
     */
    public function hydrateObject(SalesChannelContext $channelContext, $transaction = null): AbstractHeidelpayResource;
}
