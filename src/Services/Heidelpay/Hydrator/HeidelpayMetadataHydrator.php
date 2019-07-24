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
        $metadata = (new Metadata())
            ->setShopType('Shopware 6')
            ->setShopVersion(Kernel::SHOPWARE_FALLBACK_VERSION);

        //TODO: @deprecated Should be removed as soon as the shopware finalize URL is shorter so that Heidelpay can handle it!
        $metadata->addMetadata('returnUrl', $transaction->getReturnUrl());

        return $metadata;
    }
}
