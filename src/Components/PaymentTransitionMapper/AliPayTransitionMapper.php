<?php

declare(strict_types=1);

namespace HeidelPayment6\Components\PaymentTransitionMapper;

use heidelpayPHP\Resources\PaymentTypes\Alipay;
use heidelpayPHP\Resources\PaymentTypes\BasePaymentType;

class AliPayTransitionMapper extends AbstractTransitionMapper
{
    public function supports(BasePaymentType $paymentType): bool
    {
        return $paymentType instanceof Alipay;
    }

    protected function getResourceName(): string
    {
        return Alipay::getResourceName();
    }
}
