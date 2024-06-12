<?php

namespace UnzerSDK\Resources\PaymentTypes;

use UnzerSDK\Traits\CanDirectCharge;

class Wechatpay extends BasePaymentType
{
    use CanDirectCharge;
}
