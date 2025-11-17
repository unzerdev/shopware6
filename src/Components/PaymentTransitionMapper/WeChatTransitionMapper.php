<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\PaymentTransitionMapper;

use UnzerPayment6\Components\PaymentTransitionMapper\Traits\IsBasicPaymentMethodTransitionMapper;
use UnzerSDK\Resources\PaymentTypes\BasePaymentType;
use UnzerSDK\Resources\PaymentTypes\Wechatpay;

class WeChatTransitionMapper extends AbstractTransitionMapper
{
    use IsBasicPaymentMethodTransitionMapper;

    public function supports(BasePaymentType $paymentType): bool
    {
        return $paymentType instanceof Wechatpay;
    }

    protected function getResourceName(): string
    {
        return Wechatpay::getResourceName();
    }
}
