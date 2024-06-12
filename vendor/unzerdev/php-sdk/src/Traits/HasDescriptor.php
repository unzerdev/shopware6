<?php
/*
 *  Trait containing a property set of transaction regarding bank account information.
 *
 *  @link  https://docs.unzer.com/
 */

namespace UnzerSDK\Traits;

trait HasDescriptor
{
    /** @var string $descriptor */
    private $descriptor;

    /**
     * Returns the Descriptor the customer needs to use when transferring the amount.
     * E.g. invoice, prepayment, etc.
     *
     * @return string|null
     */
    public function getDescriptor(): ?string
    {
        return $this->descriptor;
    }

    /**
     * @param string|null $descriptor
     *
     * @return self
     */
    protected function setDescriptor(?string $descriptor): self
    {
        $this->descriptor = $descriptor;
        return $this;
    }
}
