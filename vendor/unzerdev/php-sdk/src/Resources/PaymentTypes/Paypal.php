<?php

namespace UnzerSDK\Resources\PaymentTypes;

use RuntimeException;
use UnzerSDK\Exceptions\UnzerApiException;
use UnzerSDK\Resources\Recurring;
use UnzerSDK\Traits\CanRecur;
use UnzerSDK\Traits\CanAuthorize;
use UnzerSDK\Traits\CanDirectCharge;

class Paypal extends BasePaymentType
{
    use CanAuthorize;
    use CanDirectCharge;
    use CanRecur {
        activateRecurring as traitActivateRecurring;
    }

    /** @var string|null $email */
    protected $email;

    /**
     * @return string|null
     */
    public function getEmail(): ?string
    {
        return $this->email;
    }

    /**
     * @param string|null $email
     *
     * @return Paypal
     */
    public function setEmail(string $email): Paypal
    {
        $this->email = $email;
        return $this;
    }

    /**
     * Activates recurring payment for Paypal.
     *
     * @param string     $returnUrl      The URL to which the customer gets redirected in case of a 3ds transaction
     * @param null|mixed $recurrenceType Recurrence type used for recurring payment.
     *
     * @return Recurring
     *
     * @throws UnzerApiException An UnzerApiException is thrown if there is an error returned on API-request.
     * @throws RuntimeException  A RuntimeException is thrown when there is an error while using the SDK.
     */
    public function activateRecurring($returnUrl, $recurrenceType = null): Recurring
    {
        return $this->traitActivateRecurring($returnUrl, $recurrenceType);
    }
}
