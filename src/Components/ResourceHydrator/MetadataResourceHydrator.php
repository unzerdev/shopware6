<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\ResourceHydrator;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Plugin\PluginEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use UnzerPayment6\UnzerPayment6;
use UnzerSDK\Resources\AbstractUnzerResource;
use UnzerSDK\Resources\Metadata;

class MetadataResourceHydrator implements ResourceHydratorInterface
{
    /** @var string */
    private $shopwareVersion;

    /** @var EntityRepository */
    private $pluginRepository;

    public function __construct(string $shopwareVersion, EntityRepository $pluginRepository)
    {
        $this->shopwareVersion  = $shopwareVersion;
        $this->pluginRepository = $pluginRepository;
    }

    public function hydrateObject(
        SalesChannelContext $channelContext,
        $transaction = null
    ): AbstractUnzerResource {
        $pluginData = $this->getPluginData($channelContext->getContext());

        $unzerMetadata = new Metadata();
        $unzerMetadata->setShopType('Shopware 6');
        $unzerMetadata->setShopVersion($this->shopwareVersion);
        $unzerMetadata->addMetadata('pluginType', 'unzerdev/shopware6');

        if ($pluginData !== null) {
            $unzerMetadata->addMetadata('pluginVersion', $pluginData->getVersion());
        }

        return $unzerMetadata;
    }

    protected function getPluginData(Context $context): ?PluginEntity
    {
        $pluginSearchCriteria = new Criteria();
        $pluginSearchCriteria->addFilter(new EqualsFilter('name', UnzerPayment6::PLUGIN_NAME));

        return $this->pluginRepository->search($pluginSearchCriteria, $context)->first();
    }
}
