<?php
/**
 * This trait adds means to determine whether the payment type is an invoice type.
 *
 * @link  https://docs.unzer.com/
 *
 */

namespace UnzerSDK\Traits;

trait IsInvoiceType
{
    /**
     * Return true for invoice types.
     * This enables you to handle the invoice workflow correctly.
     * Special to these payment types is that the initial charge transaction never changes from pending to success.
     * And that shipment is done before payment is complete.
     * Pending state of initial transaction can be viewed as successful and can be handled as such.
     *
     * @return bool
     */
    public function isInvoiceType(): bool
    {
        return true;
    }
}
