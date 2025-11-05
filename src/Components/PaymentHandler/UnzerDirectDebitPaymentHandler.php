<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\PaymentHandler;

use UnzerPayment6\Components\PaymentHandler\Traits\CanCharge;
use UnzerPayment6\Components\PaymentHandler\Traits\HasDeviceVault;
use UnzerPayment6\Components\PaymentHandler\Traits\IsBasicPaymentMethod;
use UnzerSDK\Resources\PaymentTypes\SepaDirectDebit;

class UnzerDirectDebitPaymentHandler extends AbstractUnzerPaymentHandler
{
    use CanCharge;
    use HasDeviceVault;
    use IsBasicPaymentMethod;

    protected function getUnzerPaymentTypeObject(): ?SepaDirectDebit
    {
        return new SepaDirectDebit(null);
    }
}
