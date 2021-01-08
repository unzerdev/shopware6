<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\PaymentTransitionMapper;

use UnzerSDK\Resources\PaymentTypes\BasePaymentType;
use UnzerSDK\Resources\PaymentTypes\Ideal;

class IdealTransitionMapper extends AbstractTransitionMapper
{
    public function supports(BasePaymentType $paymentType): bool
    {
        return $paymentType instanceof Ideal;
    }

    protected function getResourceName(): string
    {
        return Ideal::getResourceName();
    }
}
