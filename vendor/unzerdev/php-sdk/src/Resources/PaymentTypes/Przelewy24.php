<?php

namespace UnzerSDK\Resources\PaymentTypes;

use UnzerSDK\Traits\CanDirectCharge;

class Przelewy24 extends BasePaymentType
{
    use CanDirectCharge;
}
