<?php

namespace UnzerSDK\Resources\EmbeddedResources;

use UnzerSDK\Constants\CustomerGroups;
use UnzerSDK\Resources\AbstractUnzerResource;

/**
 * Risk class for paylater types for payment page.
 */
class Risk extends AbstractUnzerResource
{
    /** @var string|null $registrationLevel */
    protected $registrationLevel;

    /** @var string|null $registrationDate */
    protected $registrationDate;

    /** @var string|null $customerGroup */
    protected $customerGroup;

    /** @var int|null $confirmedOrders */
    protected $confirmedOrders;

    /** @var float|null $confirmedAmount */
    protected $confirmedAmount;

    /**
     * @return string|null
     */
    public function getRegistrationLevel(): ?string
    {
        return $this->registrationLevel;
    }

    /**
     * @param string|null $registrationLevel
     *
     * @return RiskData
     */
    public function setRegistrationLevel(?string $registrationLevel): Risk
    {
        $this->registrationLevel = $registrationLevel;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getRegistrationDate(): ?string
    {
        return $this->registrationDate;
    }

    /**
     * @param string|null $registrationDate Dateformat must be "YYYYMMDD".
     *
     * @return RiskData
     */
    public function setRegistrationDate(?string $registrationDate): Risk
    {
        $this->registrationDate = $registrationDate;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getCustomerGroup(): ?string
    {
        return $this->customerGroup;
    }

    /**
     * @param string|null $customerGroup
     *
     * @return RiskData
     * @see CustomerGroups
     *
     */
    public function setCustomerGroup(?string $customerGroup): Risk
    {
        $this->customerGroup = $customerGroup;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getConfirmedOrders(): ?int
    {
        return $this->confirmedOrders;
    }

    /**
     * @param int|null $confirmedOrders
     *
     * @return RiskData
     */
    public function setConfirmedOrders(?int $confirmedOrders): Risk
    {
        $this->confirmedOrders = $confirmedOrders;
        return $this;
    }

    /**
     * @return float|null
     */
    public function getConfirmedAmount(): ?float
    {
        return $this->confirmedAmount;
    }

    /**
     * @param float|null $confirmedAmount
     *
     * @return RiskData
     */
    public function setConfirmedAmount(?float $confirmedAmount): Risk
    {
        $this->confirmedAmount = $confirmedAmount;
        return $this;
    }
}
