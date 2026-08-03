<?php

namespace UnzerSDK\Resources\PaymentTypes;

use UnzerSDK\Traits\CanDirectCharge;

/** @deprecated Giropay payment type is no longer supported and will be removed in a future version. */
class Giropay extends BasePaymentType
{
    use CanDirectCharge;
}
