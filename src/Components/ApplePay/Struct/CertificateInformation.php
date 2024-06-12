<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\ApplePay\Struct;

use DateTimeInterface;
use Shopware\Core\Framework\Struct\Struct;

class CertificateInformation extends Struct
{
    public function __construct(
        protected readonly bool               $paymentProcessingValid,
        protected readonly bool               $paymentProcessingActive,
        protected readonly bool               $paymentProcessingInherited,
        protected readonly bool               $merchantIdentificationValid,
        protected readonly bool               $merchantIdentificationInherited,
        protected readonly ?DateTimeInterface $merchantIdentificationValidUntil = null
    )
    {
    }

    public function isPaymentProcessingValid(): bool
    {
        return $this->paymentProcessingValid;
    }

    public function isPaymentProcessingInherited(): bool
    {
        return $this->paymentProcessingInherited;
    }

    public function isMerchantIdentificationValid(): bool
    {
        return $this->merchantIdentificationValid;
    }

    public function isMerchantIdentificationInherited(): bool
    {
        return $this->merchantIdentificationInherited;
    }

    public function getMerchantIdentificationValidUntil(): ?DateTimeInterface
    {
        return $this->merchantIdentificationValidUntil;
    }
}
