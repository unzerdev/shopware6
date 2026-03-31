<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\ResourceHydrator;

use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Plugin\PluginEntity;
use UnzerPayment6\UnzerPayment6;
use UnzerSDK\Resources\Metadata;

readonly class MetadataResourceHydrator
{
    public function __construct(
        private string $shopwareVersion,
        private EntityRepository $pluginRepository
    ) {
    }

    public function hydrateObject(
        Context $context,
        ?OrderTransactionEntity $transaction = null,
    ): Metadata {
        $pluginData = $this->getPluginData($context);

        $unzerMetadata = new Metadata();
        $unzerMetadata->setShopType('Shopware 6');
        $unzerMetadata->setShopVersion($this->shopwareVersion);
        $unzerMetadata->addMetadata('pluginType', 'unzerdev/shopware6');

        if ($transaction instanceof OrderTransactionEntity && $transaction->getOrder() instanceof OrderEntity) {
            $unzerMetadata->addMetadata('shopwareOrderNumber', $transaction->getOrder()->getOrderNumber());
        }

        if ($pluginData !== null) {
            $unzerMetadata->addMetadata('pluginVersion', $pluginData->getVersion());
        }

        return $unzerMetadata;
    }

    public function setIsExpress(Metadata $unzerMetadata, bool $isExpress): void
    {
        $unzerMetadata->addMetadata('isExpress', $isExpress ? '1' : '0');
    }

    protected function getPluginData(Context $context): ?PluginEntity
    {
        $pluginSearchCriteria = new Criteria();
        $pluginSearchCriteria->addFilter(new EqualsFilter('name', UnzerPayment6::PLUGIN_NAME));

        return $this->pluginRepository->search($pluginSearchCriteria, $context)->first();
    }
}
