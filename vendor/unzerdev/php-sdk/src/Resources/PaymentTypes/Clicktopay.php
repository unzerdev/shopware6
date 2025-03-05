<?php

namespace UnzerSDK\Resources\PaymentTypes;

use UnzerSDK\Traits\CanAuthorize;
use UnzerSDK\Traits\CanDirectCharge;

class Clicktopay extends BasePaymentType
{
    use CanDirectCharge;
    use CanAuthorize;

    protected $mcCorrelationId;
    protected $mcCxFlowId;
    protected $mcMerchantTransactionId;
    protected $brand;

    public function __construct(
        ?string $mcCorrelationId = null,
        ?string $mcCxFlowId = null,
        ?string $mcMerchantTransactionId = null,
        ?string $brand = null
    ) {
        $this->mcCorrelationId = $mcCorrelationId;
        $this->mcCxFlowId = $mcCxFlowId;
        $this->mcMerchantTransactionId = $mcMerchantTransactionId;
        $this->brand = $brand;
    }

    public function getMcCorrelationId(): ?string
    {
        return $this->mcCorrelationId;
    }

    public function getMcCxFlowId(): ?string
    {
        return $this->mcCxFlowId;
    }

    public function getBrand(): ?string
    {
        return $this->brand;
    }

    public function getMcMerchantTransactionId(): ?string
    {
        return $this->mcMerchantTransactionId;
    }

    public function setMcCxFlowId($mcCxFlowId): self
    {
        $this->mcCxFlowId = $mcCxFlowId;
        return $this;
    }

    public function setMcCorrelationId($mcCorrelationId): self
    {
        $this->mcCorrelationId = $mcCorrelationId;
        return $this;
    }

    public function setMcMerchantTransactionId($mcMerchantTransactionId): self
    {
        $this->mcMerchantTransactionId = $mcMerchantTransactionId;
        return $this;
    }

    public function setBrand($brand): self
    {
        $this->brand = $brand;
        return $this;
    }
}
