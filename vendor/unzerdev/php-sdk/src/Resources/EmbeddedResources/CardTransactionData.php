<?php

namespace UnzerSDK\Resources\EmbeddedResources;

use UnzerSDK\Resources\AbstractUnzerResource;

/*
 * Represents `card` object of `additionalTransactionData'.
 *
 *  @link  https://docs.unzer.com/
 *
 */
class CardTransactionData extends AbstractUnzerResource
{
    /** @var string|null $recurrenceType */
    protected $recurrenceType;
    /** @var string|null $exemptionType */
    protected $exemptionType;
    /** @var string|null $liability */
    private $liability;

    /**
     * @return string|null
     */
    public function getRecurrenceType(): ?string
    {
        return $this->recurrenceType;
    }

    /**
     * @param string|null $recurrenceType
     *
     * @return CardTransactionData
     */
    public function setRecurrenceType(?string $recurrenceType): CardTransactionData
    {
        $this->recurrenceType = $recurrenceType;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getLiability(): ?string
    {
        return $this->liability;
    }

    /**
     * @param string|null $liability
     *
     * @return CardTransactionData
     */
    public function setLiability(?string $liability): CardTransactionData
    {
        $this->liability = $liability;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getExemptionType(): ?string
    {
        return $this->exemptionType;
    }

    /**
     * @param string|null $exemptionType
     *
     * @return CardTransactionData
     */
    public function setExemptionType(?string $exemptionType): CardTransactionData
    {
        $this->exemptionType = $exemptionType;
        return $this;
    }
}
