<?php

declare(strict_types=1);

namespace HeidelPayment6\Components\PaymentTransitionMapper;

use HeidelPayment6\Components\PaymentTransitionMapper\Exception\TransitionMapperException;
use HeidelPayment6\Components\PaymentTransitionMapper\AbstractTransitionMapper;
use heidelpayPHP\Resources\Payment;
use heidelpayPHP\Resources\PaymentTypes\Alipay;
use heidelpayPHP\Resources\PaymentTypes\BasePaymentType;
use heidelpayPHP\Resources\PaymentTypes\Sofort;

class SofortTransitionMapper extends AbstractTransitionMapper
{
    public function supports(BasePaymentType $paymentType): bool
    {
        return $paymentType instanceof Sofort;
    }

    public function getTargetPaymentStatus(Payment $paymentObject): string
    {
        if ($paymentObject->isPending()) {
            throw new TransitionMapperException(Sofort::getResourceName());
        }

        if ($paymentObject->isCanceled()) {
            $status = $this->checkForRefund($paymentObject);

            if ($status !== self::INVALID_STATUS) {
                return $status;
            }

            throw new TransitionMapperException(Sofort::getResourceName());
        }

        return $this->mapPaymentStatus($paymentObject);
    }
}
