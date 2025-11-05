<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\PaymentHandler\Traits;

use Shopware\Core\Checkout\Payment\Cart\PaymentTransactionStruct;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Struct\Struct;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use UnzerPayment6\DataAbstractionLayer\Entity\PaymentDevice\UnzerPaymentDeviceEntity;
use UnzerSDK\Resources\PaymentTypes\BasePaymentType;

trait IsBasicPaymentMethod
{
    use CanCharge;

    public function pay(
        Request $request,
        PaymentTransactionStruct $transaction,
        Context $context,
        ?Struct $validateStruct
    ): RedirectResponse {
        parent::pay(
            $request,
            $transaction,
            $context,
            $validateStruct
        );

        try {
            if (empty($this->paymentType) && ($unzerPaymentType = $this->getUnzerPaymentTypeObject()) !== null) {
                $this->paymentType = $this->unzerClient->createPaymentType($unzerPaymentType);
            }
            $returnUrl = $this->charge($transaction->getReturnUrl());

            $saveToDeviceVault = $request->get(self::SAVE_PAYMENT_DEVICE_KEY) !== null && \in_array(HasDeviceVault::class, class_uses(static::class), true);
            if ($saveToDeviceVault) {
                $orderTransaction = $this->transactionUtil->getOrderTransaction($transaction->getOrderTransactionId(), $context);
                $this->tryToSaveToDeviceVault(
                    $orderTransaction->getOrder()->getOrderCustomer()->getCustomerId(),
                    UnzerPaymentDeviceEntity::DEVICE_TYPE_DIRECT_DEBIT,
                    $context
                );
            }

            if (\in_array(HasTransferInfoTrait::class, class_uses(static::class), true)) {
                $orderTransaction = $this->transactionUtil->getOrderTransaction($transaction->getOrderTransactionId(), $context);
                $this->saveTransferInfo($orderTransaction, $context);
            }

            return new RedirectResponse($returnUrl);
        } catch (\Throwable $exception) {
            $this->handlePayException($exception, $request, $transaction, $context);
        }
    }

    abstract protected function getUnzerPaymentTypeObject(): ?BasePaymentType;
}
