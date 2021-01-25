<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\PaymentTransitionMapper;

use heidelpayPHP\Resources\PaymentTypes\BasePaymentType;
use heidelpayPHP\Resources\PaymentTypes\EPS;

class EpsTransitionMapper extends AbstractTransitionMapper
{
    public function supports(BasePaymentType $paymentType): bool
    {
        return $paymentType instanceof EPS;
    }

    protected function getResourceName(): string
    {
        return EPS::getResourceName();
    }

    protected function isPendingAllowed(): bool
    {
        return true;
    }
}
