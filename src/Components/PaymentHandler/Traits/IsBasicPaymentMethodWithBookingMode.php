<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\PaymentHandler\Traits;

use Shopware\Core\Checkout\Payment\Cart\PaymentTransactionStruct;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Struct\Struct;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use UnzerPayment6\Components\BookingMode;
use UnzerSDK\Resources\PaymentTypes\BasePaymentType;

trait IsBasicPaymentMethodWithBookingMode
{
    use CanAuthorize;
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
            $orderTransaction = $this->transactionUtil->getOrderTransaction($transaction->getOrderTransactionId(), $context);
            if (empty($this->paymentType) && ($unzerPaymentType = $this->getUnzerPaymentTypeObject()) !== null) {
                $this->paymentType = $this->unzerClient->createPaymentType($unzerPaymentType);
            }
            $this->setBookingMode();
            $riskData = null;
            if (\in_array(HasRiskDataTrait::class, class_uses(static::class), true)) {
                $riskData = $this->generateRiskDataResource($orderTransaction, $context);
                if ($riskData === null) {
                    throw new \RuntimeException('fraud prevention session id is missing from the current request');
                }
            }

            if ($this->bookingMode === BookingMode::CHARGE) {
                $returnUrl = $this->charge(
                    returnUrl: $transaction->getReturnUrl(),
                    riskData: $riskData
                );
                if (\in_array(HasTransferInfoTrait::class, class_uses(static::class), true)) {
                    $this->saveTransferInfo($orderTransaction, $context);
                }
            } else {
                $returnUrl = $this->authorize(
                    returnUrl: $transaction->getReturnUrl(),
                    riskData: $riskData
                );
                if (\in_array(HasTransferInfoTrait::class, class_uses(static::class), true)) {
                    $this->saveTransferInfoFromAuthorize($orderTransaction, $context);
                }
            }

            return new RedirectResponse($returnUrl);
        } catch (\Throwable $exception) {
            $this->handlePayException($exception, $request, $transaction, $context);
        }
    }

    abstract protected function getUnzerPaymentTypeObject(): ?BasePaymentType;

    abstract protected function setBookingMode(): void;
}
