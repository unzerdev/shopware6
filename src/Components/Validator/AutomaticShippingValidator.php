<?php

declare(strict_types=1);

namespace HeidelPayment6\Components\Validator;

use HeidelPayment6\Components\ConfigReader\ConfigReader;
use HeidelPayment6\Components\ConfigReader\ConfigReaderInterface;
use Shopware\Core\Checkout\Document\DocumentCollection;
use Shopware\Core\Checkout\Document\DocumentEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\System\StateMachine\Aggregation\StateMachineState\StateMachineStateEntity;

class AutomaticShippingValidator implements AutomaticShippingValidatorInterface
{
    /** @var ConfigReaderInterface */
    private $configReader;

    public function __construct(ConfigReaderInterface $configReader)
    {
        $this->configReader = $configReader;
    }

    /**
     * {@inheritdoc}
     */
    public function shouldSendAutomaticShipping(OrderEntity $orderEntity, StateMachineStateEntity $deliveryState): bool
    {
        $config             = $this->configReader->read($orderEntity->getSalesChannelId());
        $configuredStatusId = $config->get(ConfigReader::CONFIG_KEY_SHIPPING_STATUS);

        if (empty($configuredStatusId) || $deliveryState->getId() !== $configuredStatusId) {
            return false;
        }

        $orderTransaction = $orderEntity->getTransactions()->first();

        if (!$orderTransaction || !in_array($orderTransaction->getPaymentMethodId(), self::HANDLED_PAYMENT_METHODS, false)) {
            return false;
        }

        return $this->hasInvoiceDocument($orderEntity->getDocuments());
    }

    private function hasInvoiceDocument(DocumentCollection $documents): bool
    {
        return $documents->filter(static function (DocumentEntity $entity) {
            if ($entity->getDocumentType()->getTechnicalName() === 'invoice') {
                return $entity;
            }

            return null;
        })->count() > 0;
    }
}
