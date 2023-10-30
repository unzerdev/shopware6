<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\Struct\PageExtension\Checkout\Confirm;

use Shopware\Core\Framework\Struct\Struct;
use UnzerPayment6\DataAbstractionLayer\Entity\PaymentDevice\UnzerPaymentDeviceEntity;

class PayPalPageExtension extends Struct
{
    public const EXTENSION_NAME = 'unzerPayPal';

    /** @var UnzerPaymentDeviceEntity[] */
    protected $payPalAccounts = [];

    public function addPayPalAccount(UnzerPaymentDeviceEntity $paypalAccount): self
    {
        $this->payPalAccounts[] = $paypalAccount;

        return $this;
    }

    /**
     * @return UnzerPaymentDeviceEntity[]
     */
    public function getPayPalAccounts(): array
    {
        return $this->payPalAccounts;
    }

    /**
     * @param UnzerPaymentDeviceEntity[] $payPalAccounts
     *
     * @return PayPalPageExtension
     */
    public function setPayPalAccounts(array $payPalAccounts): self
    {
        $this->payPalAccounts = $payPalAccounts;

        return $this;
    }
}
