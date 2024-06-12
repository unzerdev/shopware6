<?php
/*
 *  Trait containing a property set of transaction regarding bank account information.
 *
 *  @link  https://docs.unzer.com/
 */

namespace UnzerSDK\Traits;

trait HasAccountInformation
{
    /** @var string $iban */
    private $iban;

    /** @var string bic */
    private $bic;

    /** @var string $holder */
    private $holder;

    /**
     * Returns the IBAN of the account the customer needs to transfer the amount to.
     * E.g. invoice, prepayment, etc.
     *
     * @return string|null
     */
    public function getIban(): ?string
    {
        return $this->iban;
    }

    /**
     * @param string|null $iban
     *
     * @return self
     */
    protected function setIban(?string $iban): self
    {
        $this->iban = $iban;
        return $this;
    }

    /**
     * Returns the BIC of the account the customer needs to transfer the amount to.
     * E.g. invoice, prepayment, etc.
     *
     * @return string|null
     */
    public function getBic(): ?string
    {
        return $this->bic;
    }

    /**
     * @param string|null $bic
     *
     * @return self
     */
    protected function setBic(?string $bic): self
    {
        $this->bic = $bic;
        return $this;
    }

    /**
     * Returns the holder of the account the customer needs to transfer the amount to.
     * E.g. invoice, prepayment, etc.
     *
     * @return string|null
     */
    public function getHolder(): ?string
    {
        return $this->holder;
    }

    /**
     * @param string|null $holder
     *
     * @return self
     */
    protected function setHolder(?string $holder): self
    {
        $this->holder = $holder;
        return $this;
    }
}
