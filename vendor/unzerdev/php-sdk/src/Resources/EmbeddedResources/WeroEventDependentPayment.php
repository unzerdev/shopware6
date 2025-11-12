<?php

namespace UnzerSDK\Resources\EmbeddedResources;

use UnzerSDK\Constants\WeroAmountPaymentTypes;
use UnzerSDK\Constants\WeroCaptureTriggers;
use UnzerSDK\Resources\AbstractUnzerResource;

/**
 * Represents the `eventDependentPayment` object for Wero additional transaction data.
 */
class WeroEventDependentPayment extends AbstractUnzerResource
{
    /** @see WeroCaptureTriggers */
    protected ?string $captureTrigger = null;
    /** @see WeroAmountPaymentTypes */
    protected ?string $amountPaymentType = null;
    protected ?int $maxAuthToCaptureTime = null;
    protected ?bool $multiCapturesAllowed = null;

    public function getCaptureTrigger(): ?string
    {
        return $this->captureTrigger;
    }

    /**
     * @see WeroCaptureTriggers for allowed values
     */
    public function setCaptureTrigger(?string $captureTrigger): WeroEventDependentPayment
    {
        $this->captureTrigger = $captureTrigger;
        return $this;
    }

    public function getAmountPaymentType(): ?string
    {
        return $this->amountPaymentType;
    }

    /**
     * @see WeroAmountPaymentTypes for allowed values
     */
    public function setAmountPaymentType(?string $amountPaymentType): WeroEventDependentPayment
    {
        $this->amountPaymentType = $amountPaymentType;
        return $this;
    }

    public function getMaxAuthToCaptureTime(): ?int
    {
        return $this->maxAuthToCaptureTime;
    }

    public function setMaxAuthToCaptureTime(?int $maxAuthToCaptureTime): WeroEventDependentPayment
    {
        $this->maxAuthToCaptureTime = $maxAuthToCaptureTime;
        return $this;
    }

    public function getMultiCapturesAllowed(): ?bool
    {
        return $this->multiCapturesAllowed;
    }

    public function setMultiCapturesAllowed(?bool $multiCapturesAllowed): WeroEventDependentPayment
    {
        $this->multiCapturesAllowed = $multiCapturesAllowed;
        return $this;
    }
}
