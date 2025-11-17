<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\PaymentHandler;

use UnzerPayment6\Components\PaymentHandler\Traits\IsBasicPaymentMethod;
use UnzerSDK\Resources\PaymentTypes\Twint;

class UnzerTwintPaymentHandler extends AbstractUnzerPaymentHandler
{
    use IsBasicPaymentMethod;

    protected function getUnzerPaymentTypeObject(): Twint
    {
        return new Twint();
    }
}
