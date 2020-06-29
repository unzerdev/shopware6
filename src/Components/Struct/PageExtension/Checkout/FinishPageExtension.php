<?php

declare(strict_types=1);

namespace HeidelPayment6\Components\Struct\PageExtension\Checkout;

use HeidelPayment6\Components\Struct\HirePurchase\InstallmentInfo;
use Shopware\Core\Framework\Struct\Struct;

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
