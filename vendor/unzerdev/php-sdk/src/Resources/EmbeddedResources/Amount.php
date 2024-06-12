<?php

namespace UnzerSDK\Resources\EmbeddedResources;

use UnzerSDK\Resources\AbstractUnzerResource;

/**
 * This class contains the amount properties which are mainly used by the payment class.
 *
 * @link  https://docs.unzer.com/
 *
 */
class Amount extends AbstractUnzerResource
{
    private $total = 0.0;
    private $charged = 0.0;
    private $canceled = 0.0;
    private $remaining = 0.0;

    /** @var string $currency */
    private $currency;

    /**
     * @return float
     */
    public function getTotal(): float
    {
        return $this->total;
    }

    /**
     * @param float $total
     *
     * @return $this
     */
    protected function setTotal(float $total): self
    {
        $this->total = $total;
        return $this;
    }

    /**
     * @return float
     */
    public function getCharged(): float
    {
        return $this->charged;
    }

    /**
     * @param float $charged
     *
     * @return $this
     */
    protected function setCharged(float $charged): self
    {
        $this->charged = $charged;
        return $this;
    }

    /**
     * @return float
     */
    public function getCanceled(): float
    {
        return $this->canceled;
    }

    /**
     * @param float $canceled
     *
     * @return self
     */
    protected function setCanceled(float $canceled): self
    {
        $this->canceled = $canceled;
        return $this;
    }

    /**
     * @return float
     */
    public function getRemaining(): float
    {
        return $this->remaining;
    }

    /**
     * @param float $remaining
     *
     * @return self
     */
    protected function setRemaining(float $remaining): self
    {
        $this->remaining = $remaining;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    /**
     * @param string $currency
     *
     * @return self
     */
    protected function setCurrency(string $currency): self
    {
        $this->currency = $currency;
        return $this;
    }
}
