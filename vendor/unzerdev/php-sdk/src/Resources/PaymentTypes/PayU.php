<?php

namespace UnzerSDK\Resources\PaymentTypes;

use UnzerSDK\Traits\CanDirectCharge;

class PayU extends BasePaymentType
{
    use CanDirectCharge;
}
