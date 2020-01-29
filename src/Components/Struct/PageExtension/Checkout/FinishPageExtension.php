<?php

declare(strict_types=1);

namespace HeidelPayment6\Components\Struct\PageExtension\Checkout;

use HeidelPayment6\Components\Struct\HirePurchase\InstallmentInfo;
use HeidelPayment6\Components\Struct\TransferInformation\TransferInformation;
use Shopware\Core\Framework\Struct\Struct;

class FinishPageExtension extends Struct
{
    /** @var TransferInformation[] */
    protected $transferInformation = [];

    /** @var InstallmentInfo[] */
    protected $installmentInformation = [];

    public function getTransferInformation(): array
    {
        return $this->transferInformation;
    }

    /**
     * @param TransferInformation[] $transferInformation
     *
     * @return FinishPageExtension
     */
    public function setTransferInformation(array $transferInformation): self
    {
        $this->transferInformation = $transferInformation;

        return $this;
    }

    public function addTransferInformation(TransferInformation $transferInformation): self
    {
        $this->transferInformation[] = $transferInformation;

        return $this;
    }

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
