<?php

/**
 * This trait allows a transaction type to have additional transaction Data.
 *
 * @link     https://docs.unzer.com/
 *
 */

namespace UnzerSDK\Traits;

use stdClass;
use UnzerSDK\Constants\AdditionalTransactionDataKeys;
use UnzerSDK\Resources\EmbeddedResources\CardTransactionData;
use UnzerSDK\Resources\EmbeddedResources\RiskData;
use UnzerSDK\Resources\EmbeddedResources\ShippingData;
use UnzerSDK\Resources\EmbeddedResources\WeroTransactionData;
use UnzerSDK\Resources\PaymentTypes\BasePaymentType;
use UnzerSDK\Resources\TransactionTypes\AbstractTransactionType;
use UnzerSDK\Services\ResourceService;

trait HasAdditionalTransactionData
{
    /** @var stdClass $additionalTransactionData */
    protected $additionalTransactionData;

    /** Return additionalTransactionData as a Std class object.
     *
     * @return stdClass|null
     */
    public function getAdditionalTransactionData(): ?stdClass
    {
        return $this->additionalTransactionData;
    }

    /**
     * @param stdClass $additionalTransactionData
     *
     * @return AbstractTransactionType
     */
    public function setAdditionalTransactionData(stdClass $additionalTransactionData): self
    {
        $this->additionalTransactionData = $additionalTransactionData;
        return $this;
    }

    /** Add a single element to the additionalTransactionData object.
     *
     * @param mixed $value
     * @param mixed $name
     *
     * @return AbstractTransactionType
     */
    public function addAdditionalTransactionData($name, $value): self
    {
        if (null === $this->additionalTransactionData) {
            $this->additionalTransactionData = new stdClass();
        }
        $this->additionalTransactionData->$name = $value;
        return $this;
    }

    /**
     * Sets the shipping value inside the additional transaction Data array.
     *
     * @param ShippingData|null $shippingData
     *
     * @return $this
     */
    public function setShipping(?ShippingData $shippingData): self
    {
        $this->addAdditionalTransactionData('shipping', $shippingData);
        return $this;
    }

    /**
     * Gets the shipping value from the additional transaction Data array.
     *
     * @return ShippingData|null Returns null if shipping is empty or does not contain a ShippingObject.
     */
    public function getShipping(): ?ShippingData
    {
        $shipping = $this->getAdditionalTransactionData()->shipping ?? null;
        return $shipping instanceof ShippingData ? $shipping : null;
    }

    /**
     * Sets the riskData value inside the additional transaction Data array.
     *
     * @param RiskData|null $riskData
     *
     * @return $this
     */
    public function setRiskData(?RiskData $riskData): self
    {
        $this->addAdditionalTransactionData('riskData', $riskData);
        return $this;
    }

    /**
     * Gets the riskData value from the additional transaction Data array.
     *
     * @return RiskData|null
     */
    public function getRiskData(): ?RiskData
    {
        $riskData = $this->getAdditionalTransactionData()->riskData ?? null;
        return $riskData instanceof RiskData ? $riskData : null;
    }

    /**
     * Sets the privacyPolicyUrl value inside the additional transaction Data array.
     *
     * @param string|null $privacyPolicyUrl
     *
     * @return $this
     */
    public function setPrivacyPolicyUrl(?string $privacyPolicyUrl): self
    {
        $this->addAdditionalTransactionData(AdditionalTransactionDataKeys::PRIVACY_POLICY_URL, $privacyPolicyUrl);
        return $this;
    }

    /**
     * Gets the privacyPolicyUrl value from the additional transaction Data array.
     *
     * @return string|null
     */
    public function getPrivacyPolicyUrl(): ?string
    {
        $propertyKey = AdditionalTransactionDataKeys::PRIVACY_POLICY_URL;
        return $this->getAdditionalTransactionData()->$propertyKey ?? null;
    }

    /**
     * Sets the termsAndConditionUrl value inside the additional transaction Data array.
     *
     * @param string|null $termsAndConditionUrl
     *
     * @return $this
     */
    public function setTermsAndConditionUrl(?string $termsAndConditionUrl): self
    {
        $this->addAdditionalTransactionData(AdditionalTransactionDataKeys::TERMS_AND_CONDITION_URL, $termsAndConditionUrl);
        return $this;
    }

    /**
     * Gets the termsAndConditionUrl value from the additional transaction Data array.
     *
     * @return string|null
     */
    public function getTermsAndConditionUrl(): ?string
    {
        $propertyKey = AdditionalTransactionDataKeys::TERMS_AND_CONDITION_URL;
        return $this->getAdditionalTransactionData()->$propertyKey ?? null;
    }

    /**
     * Set checkout type based on the given payment Type.
     *
     * @param string                   $checkoutType
     * @param BasePaymentType | string $paymentType  This is needed to set the correct key in additionalTransactionData array.
     *
     * @return self
     */
    public function setCheckoutType(string $checkoutType, $paymentType): self
    {
        if (is_string($paymentType)) {
            $paymentType = ResourceService::getTypeInstanceFromIdString($paymentType);
        }

        $typeName = $paymentType::getResourceName();
        if (empty($this->getAdditionalTransactionData()->$typeName)) {
            $this->addAdditionalTransactionData($typeName, new StdClass());
        }

        $checkoutTypekey = AdditionalTransactionDataKeys::CHECKOUTTYPE;
        $this->getAdditionalTransactionData()->$typeName->$checkoutTypekey = $checkoutType;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getCheckoutType(): ?string
    {
        $additionalTransactionData = $this->getAdditionalTransactionData();
        if ($additionalTransactionData !== null) {
            $key = AdditionalTransactionDataKeys::CHECKOUTTYPE;
            foreach ($additionalTransactionData as $data) {
                if ($data instanceof stdClass && property_exists($data, $key)) {
                    return $data->$key ?? null;
                }
            }
        }

        return null;
    }

    /**
     * Get the card field from additional transaction Data.
     *
     * @return CardTransactionData|null "card" object of additionalTransaction data.
     */
    public function getCardTransactionData(): ?CardTransactionData
    {
        $key = AdditionalTransactionDataKeys::CARD;
        $card = $this->getAdditionalTransactionData()->$key ?? null;
        return $card instanceof CardTransactionData ? $card : null;
    }

    /**
     * Sets CardTransactionData object as "card" field of additionalTransactionData.
     *
     * @param CardTransactionData|null $cardTransactionData
     *
     * @return self
     */
    public function setCardTransactionData(?CardTransactionData $cardTransactionData): self
    {
        $this->addAdditionalTransactionData(AdditionalTransactionDataKeys::CARD, $cardTransactionData);
        return $this;
    }

    /**
     * Get the wero field from additional transaction Data.
     *
     * @return WeroTransactionData|null "wero" object of additionalTransaction data.
     */
    public function getWeroTransactionData(): ?WeroTransactionData
    {
        $key = AdditionalTransactionDataKeys::WERO;
        $wero = $this->getAdditionalTransactionData()->$key ?? null;
        return $wero instanceof WeroTransactionData ? $wero : null;
    }

    /**
     * Sets WeroTransactionData object as "wero" field of additionalTransactionData.
     *
     * @param WeroTransactionData|null $weroTransactionData
     *
     * @return self
     */
    public function setWeroTransactionData(?WeroTransactionData $weroTransactionData): self
    {
        $this->addAdditionalTransactionData(AdditionalTransactionDataKeys::WERO, $weroTransactionData);
        return $this;
    }
}
