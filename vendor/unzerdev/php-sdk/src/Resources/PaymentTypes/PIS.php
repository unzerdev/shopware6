<?php

namespace UnzerSDK\Resources\PaymentTypes;

use UnzerSDK\Traits\CanDirectCharge;

class PIS extends BasePaymentType
{
    use CanDirectCharge;
}
