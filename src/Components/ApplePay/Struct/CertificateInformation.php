<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\ApplePay\Struct;

use DateTimeInterface;
use Shopware\Core\Framework\Struct\Struct;

class CertificateInformation extends Struct
{
    protected bool $paymentProcessingValid;
    protected bool $merchantIdentificationValid;
    protected ?DateTimeInterface $merchantIdentificationValidUntil;

    public function __construct(bool $paymentProcessingValid, bool $merchantIdentificationValid, ?DateTimeInterface $merchantIdentificationValidUntil)
    {
        $this->paymentProcessingValid           = $paymentProcessingValid;
        $this->merchantIdentificationValid      = $merchantIdentificationValid;
        $this->merchantIdentificationValidUntil = $merchantIdentificationValidUntil;
    }

    public function isPaymentProcessingValid(): bool
    {
        return $this->paymentProcessingValid;
    }

    public function isMerchantIdentificationValid(): bool
    {
        return $this->merchantIdentificationValid;
    }

    public function getMerchantIdentificationValidUntil(): ?DateTimeInterface
    {
        return $this->merchantIdentificationValidUntil;
    }
}
