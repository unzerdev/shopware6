<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\Validator;

use Shopware\Core\Checkout\Document\DocumentEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\System\StateMachine\Aggregation\StateMachineState\StateMachineStateEntity;
use UnzerPayment6\Components\ConfigReader\ConfigReader;
use UnzerPayment6\Components\ConfigReader\ConfigReaderInterface;
use UnzerPayment6\Components\TransactionSelectionHelper\TransactionSelectionHelperInterface;

class AutomaticShippingValidator implements AutomaticShippingValidatorInterface
{
    /** @var ConfigReaderInterface */
    private $configReader;

    /** @var TransactionSelectionHelperInterface */
    private $transactionSelectionHelper;

    public function __construct(
        ConfigReaderInterface $configReader,
        TransactionSelectionHelperInterface $transactionSelectionHelper
    ) {
        $this->configReader               = $configReader;
        $this->transactionSelectionHelper = $transactionSelectionHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function shouldSendAutomaticShipping(OrderEntity $orderEntity, StateMachineStateEntity $deliveryState): bool
    {
        $config             = $this->configReader->read($orderEntity->getSalesChannelId());
        $configuredStatusId = $config->get(ConfigReader::CONFIG_KEY_SHIPPING_STATUS);

        $transaction = $this->transactionSelectionHelper->getBestUnzerTransaction($orderEntity);

        if (!$transaction || !in_array($transaction->getPaymentMethodId(), self::HANDLED_PAYMENT_METHODS, false)) {
            return false;
        }

        return !(empty($configuredStatusId) || $deliveryState->getId() !== $configuredStatusId);
    }

    public function hasInvoiceDocument(OrderEntity $orderEntity): bool
    {
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
