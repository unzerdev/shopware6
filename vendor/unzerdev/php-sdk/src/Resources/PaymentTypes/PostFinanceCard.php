<?php

namespace UnzerSDK\Resources\PaymentTypes;

use UnzerSDK\Traits\CanDirectCharge;

class PostFinanceCard extends BasePaymentType
{
    use CanDirectCharge;
}
