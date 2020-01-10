<?php

declare(strict_types=1);

namespace HeidelPayment6\Components\Struct\PageExtension\Checkout;

use HeidelPayment6\Components\Struct\TransferInformation\TransferInformation;
use Shopware\Core\Framework\Struct\Struct;

class FinishPageExtension extends Struct
{
    /** @var TransferInformation[] */
    protected $transferInformation;

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
}
