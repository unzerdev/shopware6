<?php

namespace UnzerSDK\Resources\EmbeddedResources;

use UnzerSDK\Resources\AbstractUnzerResource;

/**
 * Shipping class for Paylater payment types.
 *
 * @link  https://docs.unzer.com/
 *
 */
class ShippingData extends AbstractUnzerResource
{
    /** @var string|null $deliveryTrackingId */
    protected $deliveryTrackingId;

    /** @var string|null $deliveryService */
    protected $deliveryService;

    /** @var string|null $returnTrackingId */
    protected $returnTrackingId;

    /**
     * @return string|null
     */
    public function getDeliveryTrackingId(): ?string
    {
        return $this->deliveryTrackingId;
    }

    /**
     * @param string|null $deliveryTrackingId
     *
     * @return ShippingData
     */
    public function setDeliveryTrackingId(?string $deliveryTrackingId): ShippingData
    {
        $this->deliveryTrackingId = $deliveryTrackingId;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getDeliveryService(): ?string
    {
        return $this->deliveryService;
    }

    /**
     * @param string|null $deliveryService
     *
     * @return ShippingData
     */
    public function setDeliveryService(?string $deliveryService): ShippingData
    {
        $this->deliveryService = $deliveryService;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getReturnTrackingId(): ?string
    {
        return $this->returnTrackingId;
    }

    /**
     * @param string|null $returnTrackingId
     *
     * @return ShippingData
     */
    public function setReturnTrackingId(?string $returnTrackingId): ShippingData
    {
        $this->returnTrackingId = $returnTrackingId;
        return $this;
    }
}
