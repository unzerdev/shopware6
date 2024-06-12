<?php

namespace UnzerSDK\Resources\PaymentTypes;

use UnzerSDK\Traits\CanDirectCharge;

class Prepayment extends BasePaymentType
{
    use CanDirectCharge;
}
