<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\PaymentHandler;

use UnzerPayment6\Components\BookingMode;
use UnzerPayment6\Components\PaymentHandler\Traits\CanAuthorize;
use UnzerPayment6\Components\PaymentHandler\Traits\CanCharge;
use UnzerPayment6\Components\PaymentHandler\Traits\HasRiskDataTrait;
use UnzerPayment6\Components\PaymentHandler\Traits\HasTransferInfoTrait;
use UnzerPayment6\Components\PaymentHandler\Traits\IsBasicPaymentMethodWithBookingMode;
use UnzerSDK\Resources\PaymentTypes\PaylaterInvoice;

class UnzerPaylaterInvoicePaymentHandler extends AbstractUnzerPaymentHandler
{
    use CanAuthorize;
    use CanCharge;
    use HasRiskDataTrait;
    use HasTransferInfoTrait;
    use IsBasicPaymentMethodWithBookingMode;

    protected function getUnzerPaymentTypeObject(): PaylaterInvoice
    {
        return new PaylaterInvoice();
    }

    protected function setBookingMode(): void
    {
        $this->bookingMode = BookingMode::AUTHORIZE;
    }
}
