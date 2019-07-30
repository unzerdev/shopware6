<?php

declare(strict_types=1);

namespace HeidelPayment\Components\Struct\PageExtension\Account;

use HeidelPayment\DataAbstractionLayer\Entity\PaymentDevice\HeidelpayPaymentDeviceEntity;
use Shopware\Core\Framework\Struct\Struct;

class PaymentMethodPageExtension extends Struct
{
    /** @var array<HeidelpayPaymentDeviceEntity> */
    protected $savedDevices = [];

    /** @var bool */
    protected $deviceRemoved = false;

    public function addCreditCard(HeidelpayPaymentDeviceEntity $creditCard): self
    {
        $this->savedDevices[] = $creditCard;

        return $this;
    }

    /**
     * @return array<HeidelpayPaymentDeviceEntity>
     */
    public function getSavedDevices(): array
    {
        return $this->savedDevices;
    }

    /**
     * @param array<HeidelpayPaymentDeviceEntity> $savedDevices
     *
     * @return PaymentMethodPageExtension
     */
    public function setSavedDevices(array $savedDevices): self
    {
        $this->savedDevices = $savedDevices;

        return $this;
    }

    public function isDeviceRemoved(): bool
    {
        return $this->deviceRemoved;
    }

    public function setDeviceRemoved(bool $deviceRemoved): self
    {
        $this->deviceRemoved = $deviceRemoved;

        return $this;
    }
}
