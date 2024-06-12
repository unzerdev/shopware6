<?php
/**
 * This represents the invoice payment type.
 *
 * @link  https://docs.unzer.com/
 *
 */

namespace UnzerSDK\Resources\PaymentTypes;

use UnzerSDK\Traits\CanDirectCharge;
use UnzerSDK\Traits\IsInvoiceType;

/**
 * @deprecated since 1.2.3.0 Please switch to PaylaterInvoice.
 */
class Invoice extends BasePaymentType
{
    use CanDirectCharge;
    use IsInvoiceType;
}
