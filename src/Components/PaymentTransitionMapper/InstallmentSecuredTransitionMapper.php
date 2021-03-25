<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\PaymentTransitionMapper;

use UnzerPayment6\Components\PaymentTransitionMapper\Exception\TransitionMapperException;
use UnzerSDK\Resources\Payment;
use UnzerSDK\Resources\PaymentTypes\BasePaymentType;
use UnzerSDK\Resources\PaymentTypes\InstallmentSecured;

class InstallmentSecuredTransitionMapper extends AbstractTransitionMapper
{
    public function supports(BasePaymentType $paymentType): bool
    {
        return $paymentType instanceof InstallmentSecured;
    }

    public function getTargetPaymentStatus(Payment $paymentObject): string
    {
        if ($paymentObject->isCanceled()) {
            $status = $this->checkForRefund($paymentObject);

            if ($status !== self::INVALID_TRANSITION) {
                return $status;
            }

            $status = $this->checkForCancellation($paymentObject);

            if ($status !== self::INVALID_TRANSITION) {
                return $status;
            }

            throw new TransitionMapperException($this->getResourceName());
        }

        return $this->checkForRefund($paymentObject, $this->mapPaymentStatus($paymentObject));
    }

    protected function getResourceName(): string
    {
        return InstallmentSecured::getResourceName();
    }
}
