<?php

declare(strict_types=1);

namespace HeidelPayment6\Components\PaymentTransitionMapper;

use HeidelPayment6\Components\PaymentTransitionMapper\Exception\TransitionMapperException;
use heidelpayPHP\Resources\Payment;
use heidelpayPHP\Resources\PaymentTypes\BasePaymentType;
use heidelpayPHP\Resources\PaymentTypes\InvoiceGuaranteed;

class InvoiceGuaranteedTransitionMapper extends AbstractTransitionMapper
{
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

            throw new TransitionMapperException($this->getResourceName());
        }

        $mappedStatus = $this->checkForRefund($paymentObject, $this->mapPaymentStatus($paymentObject));

        if ($paymentObject->isPending()) {
            return $this->checkForShipment($paymentObject, $mappedStatus);
        }

        return $mappedStatus;
    }

    protected function getResourceName(): string
    {
        return InvoiceGuaranteed::getResourceName();
    }
}
