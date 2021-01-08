<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\PaymentTransitionMapper;

use UnzerSDK\Resources\PaymentTypes\BasePaymentType;
use UnzerSDK\Resources\PaymentTypes\InstallmentSecured;

class InstallmentSecuredTransitionMapper extends AbstractTransitionMapper
{
    public function supports(BasePaymentType $paymentType): bool
    {
        return $paymentType instanceof InstallmentSecured;
    }

    protected function getResourceName(): string
    {
        return InstallmentSecured::getResourceName();
    }
}
