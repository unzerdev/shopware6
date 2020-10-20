<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\ResourceHydrator;

use heidelpayPHP\Resources\AbstractHeidelpayResource;
use heidelpayPHP\Resources\Metadata;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class MetadataResourceHydrator implements ResourceHydratorInterface
{
    /** @var string */
    private $shopwareVersion;

    public function __construct(string $shopwareVersion)
    {
        $this->shopwareVersion = $shopwareVersion;
    }

    public function hydrateObject(
        SalesChannelContext $channelContext,
        $transaction = null
    ): AbstractHeidelpayResource {
        $unzerMetadata = new Metadata();
        $unzerMetadata->setShopType('Shopware 6');
        $unzerMetadata->setShopVersion($this->shopwareVersion);

        return $unzerMetadata;
    }
}
