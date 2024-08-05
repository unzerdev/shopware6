<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\PaymentHandler;

use UnzerPayment6\Components\PaymentHandler\Traits\IsBasicPaymentMethod;
use UnzerSDK\Resources\PaymentTypes\Przelewy24;

class UnzerPrzelewyHandler extends AbstractUnzerPaymentHandler
{
    use IsBasicPaymentMethod;

    protected function getUnzerPaymentTypeObject(): Przelewy24
    {
        return new Przelewy24();
    }
}
