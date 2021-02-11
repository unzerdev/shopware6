<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\Validator;

use Shopware\Core\Checkout\Document\DocumentEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\System\StateMachine\Aggregation\StateMachineState\StateMachineStateEntity;
use UnzerPayment6\Components\ConfigReader\ConfigReader;
use UnzerPayment6\Components\ConfigReader\ConfigReaderInterface;

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

        return !(empty($configuredStatusId) || $deliveryState->getId() !== $configuredStatusId);
    }

    public function hasInvoiceDocument(OrderEntity $orderEntity): bool
    {
        $orderTransactions = $orderEntity->getTransactions();

        if ($orderTransactions === null) {
            return false;
        }

        $firstOrderTransaction = $orderTransactions->first();

        if (!$firstOrderTransaction || !in_array($firstOrderTransaction->getPaymentMethodId(), self::HANDLED_PAYMENT_METHODS, false)) {
            return false;
        }

        $documents = $orderEntity->getDocuments();

        if (empty($documents)) {
            return false;
        }

        return $documents->filter(static function (DocumentEntity $entity) {
            if (!$entity->getDocumentType()) {
                return null;
            }

            if ($entity->getDocumentType()->getTechnicalName() === 'invoice') {
                return $entity;
            }

            return null;
        })->count() > 0;
    }
}
