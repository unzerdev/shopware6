<?php

declare(strict_types=1);

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
        $heidelMetadata = new Metadata();
        $heidelMetadata->setShopType('Shopware 6');
        $heidelMetadata->setShopVersion(Kernel::SHOPWARE_FALLBACK_VERSION);

        //TODO: @deprecated Should be removed as soon as the shopware finalize URL is shorter so that Heidelpay can handle it!
        $heidelMetadata->addMetadata('returnUrl', $transaction->getReturnUrl());

        return $heidelMetadata;
    }
}
