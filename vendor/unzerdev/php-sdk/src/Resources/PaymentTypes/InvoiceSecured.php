<?php
/**
 * This represents the invoice secured payment type.
 *
 * @link  https://docs.unzer.com/
 *
 */

namespace UnzerSDK\Resources\PaymentTypes;

use UnzerSDK\Traits\CanDirectChargeWithCustomer;
use UnzerSDK\Traits\IsInvoiceType;

/**
 * @deprecated since 1.2.0.0 Please use PaylaterInvoice instead.
 */
class InvoiceSecured extends BasePaymentType
{
    use CanDirectChargeWithCustomer;
    use IsInvoiceType;
}
