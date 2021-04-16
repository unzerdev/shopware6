<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\ResourceHydrator;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Plugin\PluginEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use UnzerSDK\Resources\AbstractUnzerResource;
use UnzerSDK\Resources\Metadata;

class MetadataResourceHydrator implements ResourceHydratorInterface
{
    public const PLUGIN_NAME = 'UnzerPayment6';

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
    ): AbstractUnzerResource {
        $pluginData = $this->getPluginData($channelContext->getContext());

        $unzerMetadata = new Metadata();
        $unzerMetadata->setShopType('Shopware 6');
        $unzerMetadata->setShopVersion($this->shopwareVersion);
        $unzerMetadata->addMetadata('pluginType', self::PLUGIN_NAME);

        if ($pluginData !== null) {
            $unzerMetadata->addMetadata('pluginVersion', $pluginData->getVersion());
        }

        return $unzerMetadata;
    }

    protected function getPluginData(Context $context): ?PluginEntity
    {
        $pluginSearchCriteria = new Criteria();
        $pluginSearchCriteria->addFilter(new EqualsFilter('name', self::PLUGIN_NAME));

        return $this->pluginRepository->search($pluginSearchCriteria, $context)->first();
    }
}
