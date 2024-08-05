<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\PaymentHandler;

use UnzerPayment6\Components\PaymentHandler\Traits\IsBasicPaymentMethod;
use UnzerSDK\Resources\PaymentTypes\Ideal;

class UnzerIdealPaymentHandler extends AbstractUnzerPaymentHandler
{
    use IsBasicPaymentMethod;

    protected function getUnzerPaymentTypeObject(): Ideal
    {
        return new Ideal();
    }
}
