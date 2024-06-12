<?php

namespace UnzerSDK\Resources\EmbeddedResources\Paylater;

use UnzerSDK\Constants\CustomerTypes;
use UnzerSDK\Resources\AbstractUnzerResource;

/**
 * This class contains the query properties which are used to fetch paylater installment plans.
 *
 * @link  https://docs.unzer.com/
 *
 */
class InstallmentPlansQuery extends AbstractUnzerResource
{
    protected float $amount;
    protected string $currency;
    protected string $country;
    protected string $customerType;

    /**
     * @param float  $amount
     * @param string $currency
     * @param string $country
     * @param string $customerType
     */
    public function __construct(float $amount, string $currency, string $country, string $customerType = CustomerTypes::B2C)
    {
        $this->amount = $amount;
        $this->currency = $currency;
        $this->country = $country;
        $this->customerType = $customerType;
    }

    /**
     * @return float
     */
    public function getAmount(): float
    {
        return $this->amount;
    }

    /**
     * @param float $amount
     *
     * @return InstallmentPlansQuery
     */
    public function setAmount(float $amount): InstallmentPlansQuery
    {
        $this->amount = $amount;
        return $this;
    }

    /**
     * @return string
     */
    public function getCurrency(): string
    {
        return $this->currency;
    }

    /**
     * @param string $currency
     *
     * @return InstallmentPlansQuery
     */
    public function setCurrency(string $currency): InstallmentPlansQuery
    {
        $this->currency = $currency;
        return $this;
    }

    /**
     * @return string
     */
    public function getCountry(): string
    {
        return $this->country;
    }

    /**
     * @param string $country
     *
     * @return InstallmentPlansQuery
     */
    public function setCountry(string $country): InstallmentPlansQuery
    {
        $this->country = $country;
        return $this;
    }

    /**
     * @return string
     */
    public function getCustomerType(): string
    {
        return $this->customerType;
    }

    /**
     * @param string $customerType
     *
     * @return InstallmentPlansQuery
     */
    public function setCustomerType(string $customerType): InstallmentPlansQuery
    {
        $this->customerType = $customerType;
        return $this;
    }
}
