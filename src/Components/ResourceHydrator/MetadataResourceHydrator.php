<?php

declare(strict_types=1);

namespace HeidelPayment6\Components\ResourceHydrator;

use heidelpayPHP\Resources\AbstractHeidelpayResource;
use heidelpayPHP\Resources\Metadata;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Plugin\PluginEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class MetadataResourceHydrator implements ResourceHydratorInterface
{
    public const PLUGIN_NAME = 'HeidelPayment6';

    /** @var string */
    private $shopwareVersion;

    /** @var EntityRepositoryInterface */
    private $pluginRepository;

    public function __construct(string $shopwareVersion, EntityRepositoryInterface $pluginRepository)
    {
        $this->shopwareVersion  = $shopwareVersion;
        $this->pluginRepository = $pluginRepository;
    }

    public function hydrateObject(
        SalesChannelContext $channelContext,
        $transaction = null
    ): AbstractHeidelpayResource {
        $pluginData = $this->getPluginData($channelContext->getContext());

        $heidelMetadata = new Metadata();
        $heidelMetadata->setShopType('Shopware 6');
        $heidelMetadata->setShopVersion($this->shopwareVersion);

        if ($pluginData !== null) {
            $heidelMetadata->addMetadata('pluginVersion', $pluginData->getVersion());
        }
        $heidelMetadata->addMetadata('pluginType', self::PLUGIN_NAME);

        return $heidelMetadata;
    }

    protected function getPluginData(Context $context): ?PluginEntity
    {
        $pluginSearchCriteria = new Criteria();
        $pluginSearchCriteria->addFilter(new EqualsFilter('name', self::PLUGIN_NAME));

        return $this->pluginRepository->search($pluginSearchCriteria, $context)->first();
    }
}
