<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\Struct\PageExtension\Checkout\Confirm;

use Shopware\Core\Framework\Struct\Struct;

class InstallmentSecuredPageExtension extends Struct
{
    public const EXTENSION_NAME = 'unzerInstallmentSecured';

    /** @var float */
    private $amount = 0.0;

    /** @var string */
    private $currency = 'EUR';

    /** @var float */
    private $effectiveInterest = 4.5;

    /** @var string */
    private $orderDate = '';

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function setAmount(float $amount): self
    {
        $this->amount = $amount;

        return $this;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function setCurrency(string $currency): self
    {
        $this->currency = $currency;

        return $this;
    }

    public function getEffectiveInterest(): float
    {
        return $this->effectiveInterest;
    }

    public function setEffectiveInterest(float $effectiveInterest): self
    {
        $this->effectiveInterest = $effectiveInterest;

        return $this;
    }

    public function getOrderDate(): string
    {
        return $this->orderDate;
    }

    public function setOrderDate(string $orderDate): self
    {
        $this->orderDate = $orderDate;

        return $this;
    }
}
