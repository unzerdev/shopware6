<?php

namespace UnzerSDK\Resources\PaymentTypes;

use UnzerSDK\Adapter\HttpAdapterInterface;
use UnzerSDK\Resources\AbstractUnzerResource;
use UnzerSDK\Traits\HasGeoLocation;

/**
 * This represents a payment type.
 *
 * @link  https://docs.unzer.com/
 *
 */
abstract class BasePaymentType extends AbstractUnzerResource
{
    use HasGeoLocation;

    /** @var bool  */
    protected const SUPPORT_DIRECT_PAYMENT_CANCEL = false;

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
        return false;
    }

    public function supportsDirectPaymentCancel(): bool
    {
        return static::SUPPORT_DIRECT_PAYMENT_CANCEL;
    }

    /**
     * {@inheritDoc}
     */
    protected function getResourcePath(string $httpMethod = HttpAdapterInterface::REQUEST_GET): string
    {
        $path = 'types';
        if ($httpMethod !== HttpAdapterInterface::REQUEST_GET || $this->id === null) {
            $path .= '/' . parent::getResourcePath($httpMethod);
        }

        return $path;
    }

    /**
     * Returns an array containing additional parameters which are to be exposed within
     * authorize and charge transactions of the payment method.
     *
     * @return array
     */
    public function getTransactionParams(): array
    {
        return [];
    }
}
