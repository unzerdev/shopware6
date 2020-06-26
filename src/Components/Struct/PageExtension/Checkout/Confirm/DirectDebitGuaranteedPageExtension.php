<?php

declare(strict_types=1);

namespace HeidelPayment6\Components\Struct\PageExtension\Checkout\Confirm;

use HeidelPayment6\DataAbstractionLayer\Entity\PaymentDevice\HeidelpayPaymentDeviceEntity;
use Shopware\Core\Framework\Struct\Struct;

class DirectDebitGuaranteedPageExtension extends Struct
{
    /** @var HeidelpayPaymentDeviceEntity[] */
    protected $directDebitDevices = [];

    /** @var bool */
    protected $displayDirectDebitDeviceselection;

    public function addDirectDebitDevice(HeidelpayPaymentDeviceEntity $directDebitDevice): self
    {
        $this->directDebitDevices[] = $directDebitDevice;

        return $this;
    }

    /**
     * @return HeidelpayPaymentDeviceEntity[]
     */
    public function getDirectDebitDevices(): array
    {
        return $this->directDebitDevices;
    }

    /**
     * @param HeidelpayPaymentDeviceEntity[] $directDebitDevices
     */
    public function setDirectDebitDevices(array $directDebitDevices): self
    {
        $this->directDebitDevices = $directDebitDevices;

        return $this;
    }

    public function getDisplaydirectDebitDeviceselection(): bool
    {
        return $this->displayDirectDebitDeviceselection;
    }

    public function setDisplaydirectDebitDeviceselection(bool $displayDirectDebitDeviceselection): self
    {
        $this->displayDirectDebitDeviceselection = $displayDirectDebitDeviceselection;

        return $this;
    }
}
