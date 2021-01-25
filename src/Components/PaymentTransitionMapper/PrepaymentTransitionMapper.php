<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\PaymentTransitionMapper;

use heidelpayPHP\Resources\PaymentTypes\BasePaymentType;
use heidelpayPHP\Resources\PaymentTypes\Prepayment;

class PrepaymentTransitionMapper extends AbstractTransitionMapper
{
    public function supports(BasePaymentType $paymentType): bool
    {
        return $paymentType instanceof Prepayment;
    }

    protected function getResourceName(): string
    {
        return Prepayment::getResourceName();
    }

    protected function isPendingAllowed(): bool
    {
        return true;
    }
}
