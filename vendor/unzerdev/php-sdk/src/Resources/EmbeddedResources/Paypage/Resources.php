<?php

namespace UnzerSDK\Resources\EmbeddedResources\Paypage;

use UnzerSDK\Resources\AbstractUnzerResource;

class Resources extends AbstractUnzerResource
{
    protected ?string $customerId = null;
    protected ?string $basketId = null;
    protected ?string $metadataId = null;
    private ?array $paymentIds = null;

    /**
     * @param string|null $customerId
     * @param string|null $basketId
     * @param string|null $metadataId
     */
    public function __construct(?string $customerId = null, ?string $basketId = null, ?string $metadataId = null)
    {
        $this->customerId = $customerId;
        $this->basketId = $basketId;
        $this->metadataId = $metadataId;
    }

    public function getCustomerId(): ?string
    {
        return $this->customerId;
    }

    public function setCustomerId(?string $customerId): Resources
    {
        $this->customerId = $customerId;
        return $this;
    }

    public function getBasketId(): ?string
    {
        return $this->basketId;
    }

    public function setBasketId(?string $basketId): Resources
    {
        $this->basketId = $basketId;
        return $this;
    }

    public function getMetadataId(): ?string
    {
        return $this->metadataId;
    }

    public function setMetadataId(?string $metadataId): Resources
    {
        $this->metadataId = $metadataId;
        return $this;
    }

    public function getPaymentIds(): ?array
    {
        return $this->paymentIds;
    }
}
