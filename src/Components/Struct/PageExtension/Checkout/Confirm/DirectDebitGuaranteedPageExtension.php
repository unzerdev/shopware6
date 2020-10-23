<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\Struct\PageExtension\Checkout\Confirm;

use Shopware\Core\Framework\Struct\Struct;
use UnzerPayment6\DataAbstractionLayer\Entity\PaymentDevice\UnzerPaymentDeviceEntity;

class DirectDebitGuaranteedPageExtension extends Struct
{
    /** @var UnzerPaymentDeviceEntity[] */
    protected $directDebitDevices = [];

    /** @var bool */
    protected $displayDirectDebitDeviceSelection;

    public function addDirectDebitDevice(UnzerPaymentDeviceEntity $directDebitDevice): self
    {
        $this->directDebitDevices[] = $directDebitDevice;

        return $this;
    }

    /**
     * @return UnzerPaymentDeviceEntity[]
     */
    public function getDirectDebitDevices(): array
    {
        return $this->directDebitDevices;
    }

    /**
     * @param UnzerPaymentDeviceEntity[] $directDebitDevices
     */
    public function setDirectDebitDevices(array $directDebitDevices): self
    {
        $this->directDebitDevices = $directDebitDevices;

        return $this;
    }

    public function getDisplayDirectDebitDeviceSelection(): bool
    {
        return $this->displayDirectDebitDeviceSelection;
    }

    public function setDisplayDirectDebitDeviceSelection(bool $displayDirectDebitDeviceSelection): self
    {
        $this->displayDirectDebitDeviceSelection = $displayDirectDebitDeviceSelection;

        return $this;
    }
}
