<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\PaymentTransitionMapper;

use heidelpayPHP\Resources\Payment;
use heidelpayPHP\Resources\PaymentTypes\BasePaymentType;
use heidelpayPHP\Resources\PaymentTypes\InvoiceFactoring;
use heidelpayPHP\Resources\PaymentTypes\InvoiceGuaranteed;
use UnzerPayment6\Components\PaymentTransitionMapper\Exception\TransitionMapperException;

class InvoiceGuaranteedTransitionMapper extends AbstractTransitionMapper
{
    /** @var bool */
    protected $isShipmentAllowed = true;

    public function supports(BasePaymentType $paymentType): bool
    {
        return $paymentType instanceof InvoiceGuaranteed;
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
        return InvoiceFactoring::getResourceName();
    }
}
