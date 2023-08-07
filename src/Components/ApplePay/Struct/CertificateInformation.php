<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\ApplePay\Struct;

use DateTimeInterface;
use Shopware\Core\Framework\Struct\Struct;

class CertificateInformation extends Struct
{
    protected bool $paymentProcessingValid;

    protected bool $paymentProcessingInherited;

    protected bool $merchantIdentificationValid;

    protected bool $merchantIdentificationInherited;

    protected ?DateTimeInterface $merchantIdentificationValidUntil;

    public function __construct(bool $paymentProcessingValid, bool $paymentProcessingInherited, bool $merchantIdentificationValid, bool $merchantIdentificationInherited, ?DateTimeInterface $merchantIdentificationValidUntil)
    {
        $this->paymentProcessingValid           = $paymentProcessingValid;
        $this->paymentProcessingInherited       = $paymentProcessingInherited;
        $this->merchantIdentificationValid      = $merchantIdentificationValid;
        $this->merchantIdentificationInherited  = $merchantIdentificationInherited;
        $this->merchantIdentificationValidUntil = $merchantIdentificationValidUntil;
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
