<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\Struct\PageExtension\Checkout;

use Shopware\Core\Framework\Struct\Struct;
use UnzerPayment6\Components\Struct\InstallmentSecured\InstallmentInfo;

class FinishPageExtension extends Struct
{
    /** @var InstallmentInfo[] */
    protected $installmentInformation = [];

    public function getInstallmentInformation(): array
    {
        return $this->installmentInformation;
    }

    public function setInstallmentInformation(array $installmentInformation): self
    {
        $this->installmentInformation = $installmentInformation;

        return $this;
    }

    public function addInstallmentInfo(InstallmentInfo $installment): self
    {
        $this->installmentInformation[] = $installment;

        return $this;
    }
}
