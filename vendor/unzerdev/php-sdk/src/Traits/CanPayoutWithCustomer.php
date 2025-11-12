<?php
/**
 * Adds payout capability to payment types.
 *
 * @link  https://docs.unzer.com/
 *
 */

namespace UnzerSDK\Traits;

use RuntimeException;
use UnzerSDK\Exceptions\UnzerApiException;
use UnzerSDK\Interfaces\UnzerParentInterface;
use UnzerSDK\Resources\Basket;
use UnzerSDK\Resources\Customer;
use UnzerSDK\Resources\Metadata;
use UnzerSDK\Resources\TransactionTypes\Payout;

trait CanPayoutWithCustomer
{
    /**
     * Credit the given amount with the given currency to this payment type.
     * Throws UnzerApiException if the transaction could not be performed (e.g. increased risk etc.).
     *
     * @param float                $amount
     * @param string               $currency
     * @param string               $returnUrl
     * @param Customer|string      $customer
     * @param string|null          $orderId
     * @param Metadata|string|null $metadata
     * @param Basket|null          $basket    The Basket object corresponding to the payment.
     *                                        The Basket object will be created automatically if it does not exist
     *                                        yet (i.e. has no id).
     *
     * @return Payout The resulting payout object.
     *
     * @throws UnzerApiException An UnzerApiException is thrown if there is an error returned on API-request.
     * @throws RuntimeException  A RuntimeException is thrown when there is an error while using the SDK.
     */
    public function payout(
        float  $amount,
        string $currency,
        string $returnUrl,
        $customer,
        ?string $orderId = null,
        $metadata = null,
        ?Basket $basket = null
    ): Payout {
        if ($this instanceof UnzerParentInterface) {
            return $this->getUnzerObject()->payout(
                $amount,
                $currency,
                $this,
                $returnUrl,
                $customer,
                $orderId,
                $metadata,
                $basket
            );
        }

        throw new RuntimeException(
            self::class . ' must implement UnzerParentInterface to enable ' . __METHOD__ . ' transaction.'
        );
    }
}
