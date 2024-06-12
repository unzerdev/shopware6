<?php

namespace UnzerSDK\Resources\PaymentTypes;

use UnzerSDK\Traits\CanDirectCharge;

class Alipay extends BasePaymentType
{
    use CanDirectCharge;
}
