<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\Struct\PageExtension\Checkout\Confirm;

use Shopware\Core\Framework\Struct\Struct;
use UnzerPayment6\DataAbstractionLayer\Entity\PaymentDevice\UnzerPaymentDeviceEntity;

class PayPalPageExtension extends Struct
{
    /** @var UnzerPaymentDeviceEntity[] */
    protected $payPalAccounts = [];

    /** @var bool */
    protected $displayPayPalAccountselection;

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

    public function getDisplaypayPalAccountselection(): bool
    {
        return $this->displayPayPalAccountselection;
    }

    public function setDisplaypayPalAccountselection(bool $displayPayPalAccountselection): self
    {
        $this->displayPayPalAccountselection = $displayPayPalAccountselection;

        return $this;
    }
}
