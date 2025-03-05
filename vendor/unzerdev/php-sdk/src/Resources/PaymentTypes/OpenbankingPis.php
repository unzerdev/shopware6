<?php

namespace UnzerSDK\Resources\PaymentTypes;


class OpenbankingPis extends BasePaymentType
{

    /** @var string|null $ibanCountry */
    protected $ibanCountry;

    public function __construct(string $ibanCountry = null)
    {
        $this->ibanCountry = $ibanCountry;
    }

    /**
     * @return string|null
     */
    public function getIbanCountry(): ?string
    {
        return $this->ibanCountry;
    }

    /**
     * @param string|null $ibanCountry
     *
     * @return OpenbankingPis
     */
    public function setIbanCountry(string $ibanCountry): OpenbankingPis
    {
        $this->ibanCountry = $ibanCountry;
        return $this;
    }


}
