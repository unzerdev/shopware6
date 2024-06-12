<?php

namespace UnzerSDK\Resources\PaymentTypes;

use UnzerSDK\Traits\CanDirectCharge;

class Bancontact extends BasePaymentType
{
    use CanDirectCharge;

    /** @var string|null $holder */
    protected $holder;

    /**
     * Set the holder of the account.
     *
     * @param string|null $holder
     *
     * @return Bancontact
     */
    public function setHolder(?string $holder): Bancontact
    {
        $this->holder = $holder;
        return $this;
    }

    /**
     * Returns the holder of the account.
     *
     * @return string|null
     */
    public function getHolder(): ?string
    {
        return $this->holder;
    }
}
