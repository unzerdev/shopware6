<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\PaymentHandler;

use UnzerPayment6\Components\PaymentHandler\Traits\CanCharge;
use UnzerPayment6\Components\PaymentHandler\Traits\HasTransferInfoTrait;
use UnzerPayment6\Components\PaymentHandler\Traits\IsBasicPaymentMethod;
use UnzerSDK\Resources\PaymentTypes\Invoice;

class UnzerInvoicePaymentHandler extends AbstractUnzerPaymentHandler
{
    use CanCharge;
    use HasTransferInfoTrait;
    use IsBasicPaymentMethod;

    protected function getUnzerPaymentTypeObject(): Invoice
    {
        return new Invoice();
    }
}
