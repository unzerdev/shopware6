<?php

namespace UnzerSDK\Resources\EmbeddedResources;

/**
 * RiskData class for Paylater payment types.
 *
 * @link  https://docs.unzer.com/
 *
 */
class RiskData extends Risk
{
    /** @var string|null $threatMetrixId */
    protected $threatMetrixId;

    /** @var string|null $customerId */
    protected $customerId;

    /**
     * @return string|null
     */
    public function getThreatMetrixId(): ?string
    {
        return $this->threatMetrixId;
    }

    /**
     * @param string|null $threatMetrixId
     *
     * @return RiskData
     */
    public function setThreatMetrixId(?string $threatMetrixId): RiskData
    {
        $this->threatMetrixId = $threatMetrixId;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getCustomerId(): ?string
    {
        return $this->customerId;
    }

    /**
     * @param string|null $customerId
     *
     * @return RiskData
     */
    public function setCustomerId(?string $customerId): RiskData
    {
        $this->customerId = $customerId;
        return $this;
    }
}
