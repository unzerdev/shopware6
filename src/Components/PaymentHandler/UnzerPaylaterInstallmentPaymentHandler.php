<?php

namespace UnzerPayment6\Components\PaymentHandler;

use UnzerPayment6\Components\PaymentHandler\Traits\CanAuthorize;
use UnzerPayment6\Components\PaymentHandler\Traits\CanCharge;
use UnzerPayment6\Components\PaymentHandler\Traits\HasRiskDataTrait;
use UnzerPayment6\Components\PaymentHandler\Traits\HasTransferInfoTrait;

class UnzerPaylaterInstallmentPaymentHandler extends AbstractUnzerPaymentHandler
{
    use CanAuthorize;
    use CanCharge;
    use HasRiskDataTrait;
    use HasTransferInfoTrait;
}
