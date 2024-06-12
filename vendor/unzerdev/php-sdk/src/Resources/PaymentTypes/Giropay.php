<?php

namespace UnzerSDK\Resources\PaymentTypes;

use UnzerSDK\Traits\CanDirectCharge;

class Giropay extends BasePaymentType
{
    use CanDirectCharge;
}
