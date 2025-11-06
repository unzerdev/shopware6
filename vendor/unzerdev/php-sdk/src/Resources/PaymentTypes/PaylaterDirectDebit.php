<?php

namespace UnzerSDK\Resources\PaymentTypes;

class PaylaterDirectDebit extends BasePaymentType
{
    protected const SUPPORT_DIRECT_PAYMENT_CANCEL = true;

    /** @var string $iban */
    protected $iban;

    /** @var string $holder */
    protected $holder;

    public function __construct(?string $iban = null, ?string $holder = null)
    {
        $this->iban = $iban;
        $this->holder = $holder;
    }

    public function getIban(): ?string
    {
        return $this->iban;
    }

    public function setIban(string $iban): PaylaterDirectDebit
    {
        $this->iban = $iban;
        return $this;
    }

    public function getHolder(): ?string
    {
        return $this->holder;
    }

    public function setHolder(string $holder): PaylaterDirectDebit
    {
        $this->holder = $holder;
        return $this;
    }
}
