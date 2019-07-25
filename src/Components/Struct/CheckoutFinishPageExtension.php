<?php

declare(strict_types=1);

namespace HeidelPayment\Components\Struct;

use HeidelPayment\Components\Struct\TransferInformation\TransferInformation;
use Shopware\Core\Framework\Struct\Struct;

class CheckoutFinishPageExtension extends Struct
{
    /** @var array<TransferInformation> */
    protected $transferInformation;

    public function getTransferInformation(): array
    {
        return $this->transferInformation;
    }

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
