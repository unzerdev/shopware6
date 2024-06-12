<?php
/**
 * This trait allows a payment type to activate recurring payments.
 *
 * @link  https://docs.unzer.com/
 *
 */

namespace UnzerSDK\Traits;

use UnzerSDK\Exceptions\UnzerApiException;
use UnzerSDK\Resources\AbstractUnzerResource;
use UnzerSDK\Resources\Recurring;
use RuntimeException;

trait CanRecur
{
    /** @var bool $recurring */
    private $recurring = false;

    /**
     * Activates recurring payment for the payment type.
     *
     * @param string     $returnUrl      The URL to which the customer gets redirected in case of a 3ds transaction
     * @param null|mixed $recurrenceType Recurrence type used for recurring payment.
     *
     * @return Recurring
     *
     * @throws UnzerApiException An UnzerApiException is thrown if there is an error returned on API-request.
     * @throws RuntimeException  A RuntimeException is thrown when there is an error while using the SDK.
     *
     * @deprecated since 1.3.0.0 Please set the recurrence type in your Charge/Authorize object using `setRecurrenceType`
     *             before performing the transaction request.
     *
     */
    public function activateRecurring($returnUrl, $recurrenceType = null): Recurring
    {
        if ($this instanceof AbstractUnzerResource) {
            return $this->getUnzerObject()->activateRecurringPayment($this, $returnUrl, $recurrenceType);
        }
        throw new RuntimeException('Error: Recurring can not be enabled on this type.');
    }

    /**
     * @return bool
     */
    public function isRecurring(): bool
    {
        return $this->recurring;
    }

    /**
     * @param bool $active
     *
     * @return self
     */
    protected function setRecurring(bool $active): self
    {
        $this->recurring = $active;
        return $this;
    }
}
