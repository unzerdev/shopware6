<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\PaymentHandler;

use UnzerPayment6\Components\PaymentHandler\Traits\IsBasicPaymentMethod;
use UnzerSDK\Resources\PaymentTypes\Bancontact;

class UnzerBancontactHandler extends AbstractUnzerPaymentHandler
{
    use IsBasicPaymentMethod;

    protected function getUnzerPaymentTypeObject(): Bancontact
    {
        return new Bancontact();
    }
}
