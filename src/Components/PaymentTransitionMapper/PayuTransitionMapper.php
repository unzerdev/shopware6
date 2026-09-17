<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\PaymentTransitionMapper;

use UnzerPayment6\Components\PaymentTransitionMapper\Traits\IsBasicPaymentMethodTransitionMapper;
use UnzerSDK\Resources\PaymentTypes\BasePaymentType;
use UnzerSDK\Resources\PaymentTypes\PayU;

class PayuTransitionMapper extends AbstractTransitionMapper
{
    use IsBasicPaymentMethodTransitionMapper;

    public function supports(BasePaymentType $paymentType): bool
    {
        return $paymentType instanceof PayU;
    }

    protected function getResourceName(): string
    {
        return PayU::getResourceName();
    }
}
