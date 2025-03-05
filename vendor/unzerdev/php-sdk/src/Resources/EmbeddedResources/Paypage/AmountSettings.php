<?php

namespace UnzerSDK\Resources\EmbeddedResources\Paypage;

use UnzerSDK\Resources\AbstractUnzerResource;

class AmountSettings extends AbstractUnzerResource
{
    protected ?float $minimum = null;
    protected ?float $maximum = null;

    /**
     * @param float|null $minimum
     * @param float|null $maximum
     */
    public function __construct(?float $minimum = null, ?float $maximum = null)
    {
        $this->minimum = $minimum;
        $this->maximum = $maximum;
    }

    public function getMinimum(): ?float
    {
        return $this->minimum;
    }

    public function setMinimum(?float $minimum): AmountSettings
    {
        $this->minimum = $minimum;
        return $this;
    }

    public function getMaximum(): ?float
    {
        return $this->maximum;
    }

    public function setMaximum(?float $maximum): AmountSettings
    {
        $this->maximum = $maximum;
        return $this;
    }
}