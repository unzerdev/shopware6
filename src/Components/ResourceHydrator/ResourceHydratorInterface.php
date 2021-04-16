<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\ResourceHydrator;

use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use UnzerSDK\Resources\AbstractUnzerResource;

interface ResourceHydratorInterface
{
    /**
     * @param null|AsyncPaymentTransactionStruct|OrderTransactionEntity $transaction
     */
    public function hydrateObject(SalesChannelContext $channelContext, $transaction = null): AbstractUnzerResource;
}
