<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\CustomFieldsHelper;

use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use UnzerPayment6\Components\Validator\AutomaticShippingValidatorInterface;
use UnzerPayment6\Installer\CustomFieldInstaller;

class CustomFieldsHelper implements CustomFieldsHelperInterface
{
    private EntityRepository $orderTransactionRepository;

    public function __construct(EntityRepository $orderTransactionRepository)
    {
        $this->orderTransactionRepository = $orderTransactionRepository;
    }

    public function setOrderTransactionCustomFields(
        OrderTransactionEntity $transaction,
        Context $context
    ): void {
        $shipmentExecuted = !in_array(
            $transaction->getPaymentMethodId(),
            AutomaticShippingValidatorInterface::HANDLED_PAYMENT_METHODS,
            true
        );

        $customFields = $transaction->getCustomFields() ?? [];
        $customFields = array_merge($customFields, [
            CustomFieldInstaller::UNZER_PAYMENT_IS_TRANSACTION => true,
            CustomFieldInstaller::UNZER_PAYMENT_IS_SHIPPED     => $shipmentExecuted,
        ]);

        $update = [
            'id'           => $transaction->getId(),
            'customFields' => $customFields,
        ];

        $this->orderTransactionRepository->update([$update], $context);
    }

    public function setOrderTransactionUnzerFlag(OrderTransactionEntity $transaction, Context $context): void
    {
        $customFields = $transaction->getCustomFields() ?? [];
        $customFields = array_merge($customFields, [
            CustomFieldInstaller::UNZER_PAYMENT_IS_TRANSACTION => true,
        ]);

        $update = [
            'id'           => $transaction->getId(),
            'customFields' => $customFields,
        ];

        $this->orderTransactionRepository->update([$update], $context);
    }
}
