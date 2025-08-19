<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\PaymentHandler\Traits;

use Shopware\Core\Checkout\Payment\Cart\PaymentTransactionStruct;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Struct\Struct;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Throwable;
use UnzerSDK\Resources\PaymentTypes\BasePaymentType;

trait IsBasicPaymentMethod
{
    use CanCharge;

    public function pay(
        Request                  $request,
        PaymentTransactionStruct $transaction,
        Context                  $context,
        ?Struct                  $validateStruct
    ): RedirectResponse
    {
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
            if (in_array(HasTransferInfoTrait::class, class_uses(get_class($this)))) {
                $orderTransaction = $this->transactionUtil->getOrderTransaction($transaction->getOrderTransactionId(), $context);
                $this->saveTransferInfo($orderTransaction, $context);
            }
            return new RedirectResponse($returnUrl);
        } catch (Throwable $exception) {
            $this->handlePayException($exception, $request, $transaction, $context);
        }
    }

    abstract protected function getUnzerPaymentTypeObject(): ?BasePaymentType;
}
