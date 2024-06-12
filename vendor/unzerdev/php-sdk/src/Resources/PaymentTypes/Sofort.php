<?php

namespace UnzerSDK\Resources\PaymentTypes;

use UnzerSDK\Traits\CanDirectCharge;
use UnzerSDK\Traits\HasAccountInformation;

class Sofort extends BasePaymentType
{
    use HasAccountInformation;
    use CanDirectCharge;
}
