<?php

namespace HeidelPayment\Services\Heidelpay\Hydrator;

use heidelpayPHP\Resources\AbstractHeidelpayResource;
use heidelpayPHP\Resources\Metadata;
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\Kernel;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class HeidelpayMetadataHydrator implements HeidelpayHydratorInterface
{
    public function hydrateObject(
        SalesChannelContext $channelContext,
        ?AsyncPaymentTransactionStruct $transaction = null
    ): AbstractHeidelpayResource {
        return (new Metadata())
            ->setShopType('Shopware 6')
            ->setShopVersion(Kernel::SHOPWARE_FALLBACK_VERSION);
    }
}
