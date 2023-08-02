<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\Struct\PageExtension\Checkout\Confirm;

use Shopware\Core\Framework\Struct\Struct;
use UnzerPayment6\DataAbstractionLayer\Entity\PaymentDevice\UnzerPaymentDeviceEntity;

class DirectDebitPageExtension extends Struct
{
    public const EXTENSION_NAME = 'unzerDirectDebit';

    /** @var UnzerPaymentDeviceEntity[] */
    protected $directDebitDevices = [];

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
}
