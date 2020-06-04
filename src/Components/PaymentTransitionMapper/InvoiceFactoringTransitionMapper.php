<?php

declare(strict_types=1);

namespace HeidelPayment6\Components\PaymentTransitionMapper;

use HeidelPayment6\Components\PaymentTransitionMapper\Exception\TransitionMapperException;
use HeidelPayment6\Components\PaymentTransitionMapper\AbstractTransitionMapper;
use heidelpayPHP\Resources\Payment;
use heidelpayPHP\Resources\PaymentTypes\Alipay;
use heidelpayPHP\Resources\PaymentTypes\BasePaymentType;
use heidelpayPHP\Resources\PaymentTypes\InvoiceFactoring;

class InvoiceFactoringTransitionMapper extends AbstractTransitionMapper
{
    public function supports(BasePaymentType $paymentType): bool
    {
        return $paymentType instanceof InvoiceFactoring;
    }

    public function getTargetPaymentStatus(Payment $paymentObject): string
    {
        if ($paymentObject->isPending()) {
            throw new TransitionMapperException(InvoiceFactoring::getResourceName());
        }

        if ($paymentObject->isCanceled()) {
            $status = $this->checkForRefund($paymentObject);

            if ($status !== self::INVALID_STATUS) {
                return $status;
            }

            throw new TransitionMapperException(InvoiceFactoring::getResourceName());
        }

        return $this->mapPaymentStatus($paymentObject);
    }
}
